<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FirestoreService;
use App\Services\FirestoreUserService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CardController extends Controller
{
    public function __construct(
        private readonly FirestoreService $firestore,
        private readonly FirestoreUserService $firestoreUsers,
    ) {}

    private function getFirestoreUid(Request $request): ?string
    {
        $user = $this->firestoreUsers->findByEmail($request->user()->email);
        return $user['id'] ?? null;
    }

    // GET /api/fuel-cards — list all cards
    public function index(Request $request)
    {
        $uid = $this->getFirestoreUid($request);
        if (!$uid) return response()->json(['message' => 'User not found'], 404);

        $cards = $this->firestore->subList('users', $uid, 'fuel_cards');

        return response()->json([
            'cards' => array_map(fn($card) => $this->formatCard($card), $cards),
        ]);
    }

    // GET /api/fuel-cards/show — get primary card
    public function show(Request $request)
    {
        $uid = $this->getFirestoreUid($request);
        if (!$uid) return response()->json(['message' => 'User not found'], 404);

        $cards = $this->firestore->subList('users', $uid, 'fuel_cards');

        if (empty($cards)) {
            return response()->json(['message' => 'Aucune carte trouvée'], 404);
        }

        $card = $cards[0];
        return response()->json($this->formatCard($card, detailed: true));
    }

    // POST /api/fuel-cards/recharge
    public function recharge(Request $request)
    {
        $request->validate(['amount' => 'required|numeric|min:1|max:1000']);

        $uid = $this->getFirestoreUid($request);
        if (!$uid) return response()->json(['message' => 'User not found'], 404);

        $cards = $this->firestore->subList('users', $uid, 'fuel_cards');
        if (empty($cards)) return response()->json(['message' => 'Carte non trouvée'], 404);

        $card = $cards[0];
        $newBalance = (float)($card['balance'] ?? 0) + (float)$request->amount;

        $updated = $this->firestore->subUpdate('users', $uid, 'fuel_cards', $card['id'], [
            'balance' => $newBalance,
        ]);

        return response()->json([
            'message' => 'Carte rechargée avec succès',
            'new_balance' => number_format($newBalance, 2) . ' TND',
            'balance_raw' => $newBalance,
        ]);
    }

    // GET /api/fuel-cards/transactions — last 20 transactions of primary card
    public function transactions(Request $request)
    {
        $uid = $this->getFirestoreUid($request);
        if (!$uid) return response()->json(['message' => 'User not found'], 404);

        $cards = $this->firestore->subList('users', $uid, 'fuel_cards');
        if (empty($cards)) return response()->json(['message' => 'Carte non trouvée'], 404);

        $card = $cards[0];
        $transactions = $this->firestore->subList('users', $uid, 'transactions');
        $vehicles = $this->buildVehicleMap($uid);

        $filtered = array_filter($transactions, fn($t) => ($t['fuel_card_id'] ?? '') === $card['id']);
        usort($filtered, fn($a, $b) => strcmp($b['date'] ?? '', $a['date'] ?? ''));
        $filtered = array_slice(array_values($filtered), 0, 20);

        return response()->json([
            'card_id'      => $card['id'],
            'transactions' => array_map(fn($t) => $this->formatTransaction($t, vehicles: $vehicles), $filtered),
            'total_count'  => count($filtered),
        ]);
    }

    // GET /api/fuel-cards/history — full history with filters
    public function history(Request $request)
    {
        $uid = $this->getFirestoreUid($request);
        if (!$uid) return response()->json(['message' => 'User not found'], 404);

        $transactions = $this->firestore->subList('users', $uid, 'transactions');
        $vehicles = $this->buildVehicleMap($uid);

        if ($request->month) {
            $year = $request->year ?? now()->year;
            $transactions = array_filter($transactions, function ($t) use ($request, $year) {
                $date = Carbon::parse($t['date'] ?? '');
                return $date->month == $request->month && $date->year == $year;
            });
        }

        if ($request->station) {
            $transactions = array_filter($transactions, fn($t) =>
                str_contains(strtolower($t['station_name'] ?? ''), strtolower($request->station))
            );
        }

        usort($transactions, fn($a, $b) => strcmp($b['date'] ?? '', $a['date'] ?? ''));
        $transactions = array_values($transactions);

        $formatted = array_map(fn($t) => $this->formatTransaction($t, full: true, vehicles: $vehicles), $transactions);

        $grouped = [];
        foreach ($formatted as $t) {
            $grouped[$t['month_label']][] = $t;
        }

        $totalSpent  = array_sum(array_map(fn($t) => (float)($t['amount'] ?? 0), $formatted));
        $totalLiters = array_sum(array_map(fn($t) => (float)($t['quantity_liters'] ?? 0), $formatted));

        return response()->json([
            'transactions' => $grouped,
            'total_count'  => count($formatted),
            'total_spent'  => number_format($totalSpent, 2) . ' TND',
            'total_liters' => number_format($totalLiters, 1) . ' L',
        ]);
    }

    // POST /api/fuel-cards — create a new card
    public function store(Request $request)
    {
        $request->validate([
            'card_number'  => 'required|string',
            'issuer'       => 'nullable|string',
            'expiry_month' => 'nullable|string',
            'expiry_year'  => 'nullable|string',
            'balance'      => 'nullable|numeric',
        ]);

        $uid = $this->getFirestoreUid($request);
        if (!$uid) return response()->json(['message' => 'User not found'], 404);

        $card = $this->firestore->subCreate('users', $uid, 'fuel_cards', [
            'card_number'  => $request->card_number,
            'issuer'       => $request->input('issuer', 'Fuelix'),
            'expiry_month' => $request->input('expiry_month', '12'),
            'expiry_year'  => $request->input('expiry_year', '27'),
            'balance'      => (float) $request->input('balance', 0),
            'status'       => 'active',
        ]);

        return response()->json(['message' => 'Card created', 'card' => $this->formatCard($card)], 201);
    }

    // POST /api/transactions — create a transaction
    public function storeTransaction(Request $request)
    {
        $request->validate([
            'fuel_card_id'    => 'required|string',
            'vehicle_id'      => 'nullable|string',
            'amount'          => 'required|numeric',
            'quantity_liters' => 'required|numeric',
            'price_per_liter' => 'required|numeric',
            'station_name'    => 'nullable|string',
            'date'            => 'nullable|string',
        ]);

        $uid = $this->getFirestoreUid($request);
        if (!$uid) return response()->json(['message' => 'User not found'], 404);

        // Deduct from card balance
        $card = $this->firestore->subGet('users', $uid, 'fuel_cards', $request->fuel_card_id);
        if ($card) {
            $newBalance = max(0, (float)($card['balance'] ?? 0) - (float)$request->amount);
            $this->firestore->subUpdate('users', $uid, 'fuel_cards', $card['id'], ['balance' => $newBalance]);
        }

        $data = [
            'fuel_card_id'    => $request->fuel_card_id,
            'amount'          => (float) $request->amount,
            'quantity_liters' => (float) $request->quantity_liters,
            'price_per_liter' => (float) $request->price_per_liter,
            'station_name'    => $request->input('station_name', ''),
            'date'            => $request->input('date', now()->toIso8601String()),
        ];

        if ($request->filled('vehicle_id')) {
            $data['vehicle_id'] = $request->vehicle_id;
        }

        $transaction = $this->firestore->subCreate('users', $uid, 'transactions', $data);

        return response()->json(['message' => 'Transaction recorded', 'transaction' => $transaction], 201);
    }

    // GET /api/vehicles
    public function listVehicles(Request $request)
    {
        $uid = $this->getFirestoreUid($request);
        if (!$uid) return response()->json(['message' => 'User not found'], 404);

        $vehicles = $this->firestore->subList('users', $uid, 'vehicles');
        return response()->json(['vehicles' => $vehicles]);
    }

    // POST /api/vehicles
    public function storeVehicle(Request $request)
    {
        $request->validate([
            'plate_number'        => 'required|string',
            'model'               => 'required|string',
            'fuel_type'           => 'required|string',
            'average_consumption' => 'nullable|numeric',
        ]);

        $uid = $this->getFirestoreUid($request);
        if (!$uid) return response()->json(['message' => 'User not found'], 404);

        $vehicle = $this->firestore->subCreate('users', $uid, 'vehicles', [
            'plate_number'        => $request->plate_number,
            'model'               => $request->model,
            'fuel_type'           => $request->fuel_type,
            'average_consumption' => (float) $request->input('average_consumption', 0),
        ]);

        return response()->json(['message' => 'Vehicle added', 'vehicle' => $vehicle], 201);
    }

    // -------------------------------------------------------------------------
    // Formatters
    // -------------------------------------------------------------------------

    private function formatCard(array $card, bool $detailed = false): array
    {
        $balance = (float)($card['balance'] ?? 0);
        $expiryMonth = $card['expiry_month'] ?? '12';
        $expiryYear = substr($card['expiry_year'] ?? '27', -2);
        $cardNumber = $card['card_number'] ?? '';
        $maskedNumber = strlen($cardNumber) >= 4 ? '**** ' . substr($cardNumber, -4) : '****';
        $status = $card['status'] ?? 'active';

        $base = [
            'id'             => $card['id'],
            'masked_number'  => $maskedNumber,
            'issuer'         => $card['issuer'] ?? 'Fuelix',
            'valid_thru'     => "{$expiryMonth}/{$expiryYear}",
            'balance'        => number_format($balance, 2) . ' TND',
            'balance_raw'    => $balance,
            'status'         => $status,
            'can_pay'        => $balance > 0 && $status === 'active',
            'color'          => $card['color'] ?? '#1B3A6B',
            'card_plan_name' => $card['card_plan_name'] ?? null,
            'card_plan_id'   => $card['card_plan_id'] ?? null,
        ];

        if ($detailed) {
            $base['authorized_products'] = $card['authorized_products'] ?? null;
            $base['is_expired'] = $this->isExpired($card);
        }

        return $base;
    }

    private function formatTransaction(array $t, bool $full = false, array $vehicles = []): array
    {
        $date = Carbon::parse($t['date'] ?? now());

        // Resolve vehicle from preloaded map
        $vehicleId = $t['vehicle_id'] ?? null;
        $vehicle = null;
        if ($vehicleId && isset($vehicles[$vehicleId])) {
            $v = $vehicles[$vehicleId];
            $vehicle = [
                'id'           => $v['id'],
                'model'        => $v['model'] ?? '—',
                'plate_number' => $v['plate_number'] ?? '—',
                'fuel_type'    => $v['fuel_type'] ?? '—',
            ];
        }

        // Parse authorized_products — stored as JSON string in Firestore
        $rawProducts = $t['authorized_products'] ?? null;
        $products = [];
        if (is_string($rawProducts) && $rawProducts !== '') {
            $decoded = json_decode($rawProducts, true);
            $products = is_array($decoded) ? $decoded : [];
        } elseif (is_array($rawProducts)) {
            $products = $rawProducts;
        }

        $base = [
            'id'                  => $t['id'],
            'date'                => $date->format('Y-m-d'),
            'time'                => $date->format('H:i'),
            'amount'              => number_format((float)($t['amount'] ?? 0), 2),
            'quantity_liters'     => number_format((float)($t['quantity_liters'] ?? 0), 1),
            'price_per_liter'     => number_format((float)($t['price_per_liter'] ?? 0), 3),
            'station_name'        => $t['station_name'] ?? '',
            'authorized_products' => $products,
            'vehicle'             => $vehicle,
        ];

        if ($full) {
            $base['month_label'] = $date->format('M Y');
        }

        return $base;
    }

    /**
     * Load all vehicles for a user and index them by ID for O(1) lookup.
     */
    private function buildVehicleMap(string $uid): array
    {
        $vehicles = $this->firestore->subList('users', $uid, 'vehicles');
        $map = [];
        foreach ($vehicles as $v) {
            $map[$v['id']] = $v;
        }
        return $map;
    }

    private function isExpired(array $card): bool
    {
        $month = $card['expiry_month'] ?? null;
        $year  = $card['expiry_year'] ?? null;
        if (!$month || !$year) return false;

        $fullYear = strlen((string)$year) === 2 ? '20' . $year : $year;
        $expiry = Carbon::createFromDate($fullYear, $month, 1)->endOfMonth();
        return now()->isAfter($expiry);
    }
}
