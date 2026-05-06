<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CardPlan;
use App\Services\FirestoreService;
use App\Services\FirestoreUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CardPlanController — Admin-only controller.
 *
 * Manages card plans (tiers) stored in Firestore collection: card_plans
 * and assigns them to users' fuel cards.
 *
 * Routes (all require auth:sanctum + admin middleware):
 *   GET    /admin/card-plans              → index()
 *   POST   /admin/card-plans              → store()
 *   PUT    /admin/card-plans/{id}         → update()
 *   DELETE /admin/card-plans/{id}         → destroy()
 *   POST   /admin/card-plans/seed         → seed()
 *   POST   /admin/card-plans/assign       → assignToUser()
 */
class CardPlanController extends Controller
{
    private const COLLECTION = 'card_plans';

    public function __construct(
        private readonly FirestoreService $firestore,
        private readonly FirestoreUserService $firestoreUsers,
    ) {}

    // -------------------------------------------------------------------------
    // GET /admin/card-plans — List all card plans
    // -------------------------------------------------------------------------

    public function index(): JsonResponse
    {
        $plans = $this->firestore->list(self::COLLECTION);

        usort($plans, fn($a, $b) => ($a['tier_level'] ?? 0) <=> ($b['tier_level'] ?? 0));

        return response()->json([
            'plans' => array_map(fn($p) => $this->formatPlan($p), $plans),
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /admin/card-plans — Create a new card plan
    // -------------------------------------------------------------------------

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'                 => 'required|string|max:50',
            'description'          => 'nullable|string|max:255',
            'color'                => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'authorized_products'  => 'required|array|min:1',
            'authorized_products.*'=> 'string|in:fuel,carwash,lubricants',
            'tier_level'           => 'required|integer|min:1|max:10',
        ]);

        $plan = $this->firestore->create(self::COLLECTION, [
            'name'                => $request->name,
            'description'         => $request->input('description', ''),
            'color'               => $request->color,
            'authorized_products' => json_encode($request->authorized_products),
            'tier_level'          => (int) $request->tier_level,
            'is_active'           => true,
        ]);

        return response()->json([
            'message' => 'Card plan created successfully',
            'plan'    => $this->formatPlan($plan),
        ], 201);
    }

    // -------------------------------------------------------------------------
    // PUT /admin/card-plans/{planId} — Update a card plan
    // -------------------------------------------------------------------------

    public function update(Request $request, string $planId): JsonResponse
    {
        $request->validate([
            'name'                 => 'sometimes|string|max:50',
            'description'          => 'sometimes|nullable|string|max:255',
            'color'                => 'sometimes|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'authorized_products'  => 'sometimes|array|min:1',
            'authorized_products.*'=> 'string|in:fuel,carwash,lubricants',
            'tier_level'           => 'sometimes|integer|min:1',
            'is_active'            => 'sometimes|boolean',
        ]);

        $data = $request->only(['name', 'description', 'color', 'tier_level', 'is_active']);

        if ($request->has('authorized_products')) {
            $data['authorized_products'] = json_encode($request->authorized_products);
        }

        $updated = $this->firestore->update(self::COLLECTION, $planId, $data);

        if (!$updated) {
            return response()->json(['message' => 'Card plan not found'], 404);
        }

        return response()->json([
            'message' => 'Card plan updated successfully',
            'plan'    => $this->formatPlan($updated),
        ]);
    }

    // -------------------------------------------------------------------------
    // DELETE /admin/card-plans/{planId} — Delete a card plan
    // -------------------------------------------------------------------------

    public function destroy(string $planId): JsonResponse
    {
        $plan = $this->firestore->get(self::COLLECTION, $planId);

        if (!$plan) {
            return response()->json(['message' => 'Card plan not found'], 404);
        }

        $this->firestore->delete(self::COLLECTION, $planId);

        return response()->json(['message' => 'Card plan deleted successfully']);
    }

    // -------------------------------------------------------------------------
    // POST /admin/card-plans/seed — Seed the 3 default plans into Firestore
    // -------------------------------------------------------------------------

    public function seed(): JsonResponse
    {
        $defaults = CardPlan::defaultPlans();
        $seeded = [];

        foreach ($defaults as $plan) {
            // Skip if already exists
            $existing = $this->firestore->get(self::COLLECTION, $plan['id']);
            if ($existing) {
                $seeded[] = ['id' => $plan['id'], 'status' => 'already_exists'];
                continue;
            }

            $data = [
                'name'                => $plan['name'],
                'description'         => $plan['description'],
                'color'               => $plan['color'],
                'authorized_products' => json_encode($plan['authorized_products']),
                'tier_level'          => $plan['tier_level'],
                'is_active'           => true,
            ];

            // Use documentUrl directly to set a fixed ID
            $created = $this->firestore->create(self::COLLECTION, $data);
            $seeded[] = ['id' => $created['id'], 'name' => $plan['name'], 'status' => 'created'];
        }

        return response()->json([
            'message' => 'Default card plans seeded',
            'plans'   => $seeded,
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /admin/card-plans/assign — Assign a plan to a user's fuel card
    //
    // Body: { "user_id": "...", "card_id": "...", "plan_id": "..." }
    // -------------------------------------------------------------------------

    public function assignToUser(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|string',
            'card_id' => 'required|string',
            'plan_id' => 'required|string',
        ]);

        // Verify plan exists
        $plan = $this->firestore->get(self::COLLECTION, $request->plan_id);
        if (!$plan || !($plan['is_active'] ?? false)) {
            return response()->json(['message' => 'Card plan not found or inactive'], 404);
        }

        // Verify user exists in Firestore
        $user = $this->firestoreUsers->findById($request->user_id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Verify card belongs to user
        $card = $this->firestore->subGet('users', $request->user_id, 'fuel_cards', $request->card_id);
        if (!$card) {
            return response()->json(['message' => 'Fuel card not found for this user'], 404);
        }

        // Assign plan to card — update authorized_products and card_plan_id
        $updated = $this->firestore->subUpdate(
            'users',
            $request->user_id,
            'fuel_cards',
            $request->card_id,
            [
                'card_plan_id'        => $plan['id'],
                'card_plan_name'      => $plan['name'],
                'color'               => $plan['color'],
                'authorized_products' => $plan['authorized_products'],
            ]
        );

        return response()->json([
            'message' => "Plan \"{$plan['name']}\" assigned to card successfully",
            'card'    => $updated,
            'plan'    => $this->formatPlan($plan),
        ]);
    }

    // -------------------------------------------------------------------------
    // Formatter
    // -------------------------------------------------------------------------

    private function formatPlan(array $plan): array
    {
        $rawProducts = $plan['authorized_products'] ?? [];
        if (is_string($rawProducts)) {
            $decoded = json_decode($rawProducts, true);
            $rawProducts = is_array($decoded) ? $decoded : [];
        }

        return [
            'id'                  => $plan['id'],
            'name'                => $plan['name'] ?? '',
            'description'         => $plan['description'] ?? '',
            'color'               => $plan['color'] ?? '#CCCCCC',
            'tier_level'          => (int) ($plan['tier_level'] ?? 1),
            'authorized_products' => $rawProducts,
            'is_active'           => (bool) ($plan['is_active'] ?? true),
        ];
    }
}
