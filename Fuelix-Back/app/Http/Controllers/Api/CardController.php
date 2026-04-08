<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FuelCard;
use Illuminate\Http\Request;

class CardController extends Controller
{
    // Afficher la carte de l'utilisateur
    public function show(Request $request)
    {
        $user = $request->user();
        $card = $user->fuelCards()->first();

        if (!$card) {
            return response()->json([
                'message' => 'Aucune carte trouvée'
            ], 404);
        }

        // Vérifier et mettre à jour le statut si expirée
        $card->checkAndUpdateStatus();

        return response()->json([
            'id'                  => $card->id,
            'masked_number'       => $card->masked_number,
            'card_number'         => $card->card_number, // Pour les tests, à retirer en prod
            'issuer'              => $card->issuer,
            'valid_thru'          => $card->valid_thru,
            'balance'             => number_format($card->balance, 2) . ' TND',
            'balance_raw'         => $card->balance,
            'authorized_products' => $card->authorized_products,
            'status'              => $card->status,
            'is_expired'          => $card->isExpired(),
            'can_pay'             => $card->balance > 0 && $card->status === 'active',
            'vehicle'             => $card->vehicle ? [
                'id' => $card->vehicle->id,
                'plate_number' => $card->vehicle->plate_number,
                'model' => $card->vehicle->model,
            ] : null,
        ]);
    }

    // Recharger la carte
    public function recharge(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1|max:1000'
        ]);

        $user = $request->user();
        $card = $user->fuelCards()->first();

        if (!$card) {
            return response()->json(['message' => 'Carte non trouvée'], 404);
        }

        if ($card->recharge($request->amount)) {
            return response()->json([
                'message' => 'Carte rechargée avec succès',
                'new_balance' => number_format($card->balance, 2) . ' TND',
                'balance_raw' => $card->balance,
            ]);
        }

        return response()->json(['message' => 'Échec de la recharge'], 400);
    }

    // Historique des transactions de la carte
    public function transactions(Request $request)
    {
        $user = $request->user();
        $card = $user->fuelCards()->first();

        if (!$card) {
            return response()->json(['message' => 'Carte non trouvée'], 404);
        }

        $transactions = $card->transactions()
            ->with('vehicle:id,plate_number,model')
            ->orderBy('date', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'date' => $transaction->date->format('Y-m-d H:i'),
                    'amount' => number_format($transaction->amount, 2) . ' TND',
                    'quantity_liters' => $transaction->quantity_liters . 'L',
                    'price_per_liter' => number_format($transaction->price_per_liter, 3) . ' TND',
                    'station_name' => $transaction->station_name,
                    'vehicle' => $transaction->vehicle ? [
                        'plate_number' => $transaction->vehicle->plate_number,
                        'model' => $transaction->vehicle->model,
                    ] : null,
                ];
            });

        return response()->json([
            'card_id' => $card->id,
            'transactions' => $transactions,
            'total_count' => $card->transactions()->count(),
        ]);
    }

    // Lister toutes les cartes de l'utilisateur
    public function index(Request $request)
    {
        $user = $request->user();
        $cards = $user->fuelCards()->with('vehicle')->get();

        return response()->json([
            'cards' => $cards->map(function ($card) {
                $card->checkAndUpdateStatus();
                return [
                    'id' => $card->id,
                    'masked_number' => $card->masked_number,
                    'issuer' => $card->issuer,
                    'valid_thru' => $card->valid_thru,
                    'balance' => number_format($card->balance, 2) . ' TND',
                    'status' => $card->status,
                    'is_expired' => $card->isExpired(),
                    'vehicle' => $card->vehicle ? $card->vehicle->plate_number : null,
                ];
            }),
        ]);
    }

    // Historique complet des transactions de l'utilisateur
    public function history(Request $request)
    {
        $user = $request->user();

        $query = $user->transactions()
            ->with('vehicle:id,plate_number,model')
            ->orderBy('date', 'desc');

        // Filtre par mois
        if ($request->month) {
            $query->whereMonth('date', $request->month)
                  ->whereYear('date', $request->year ?? now()->year);
        }

        // Filtre par station
        if ($request->station) {
            $query->where('station_name', 'like', '%' . $request->station . '%');
        }

        $transactions = $query->get()->map(function ($t) {
            return [
                'id'              => $t->id,
                'date'            => $t->date->format('Y-m-d'),
                'time'            => $t->date->format('H:i'),
                'month_label'     => $t->date->format('M Y'),
                'amount'          => number_format($t->amount, 2),
                'quantity_liters' => number_format($t->quantity_liters, 1),
                'price_per_liter' => number_format($t->price_per_liter, 3),
                'station_name'    => $t->station_name,
                'vehicle'         => $t->vehicle ? [
                    'plate_number' => $t->vehicle->plate_number,
                    'model'        => $t->vehicle->model,
                ] : null,
            ];
        });

        // Grouper par mois
        $grouped = $transactions->groupBy('month_label');

        // Stats globales
        $total_spent = $user->transactions()->sum(\DB::raw('quantity_liters * price_per_liter'));
        $total_liters = $user->transactions()->sum('quantity_liters');

        return response()->json([
            'transactions' => $grouped,
            'total_count'  => $transactions->count(),
            'total_spent'  => number_format($total_spent, 2) . ' TND',
            'total_liters' => number_format($total_liters, 1) . ' L',
        ]);
    }
}