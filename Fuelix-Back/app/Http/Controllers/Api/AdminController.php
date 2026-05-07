<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FirestoreService;
use App\Services\FirestoreUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function __construct(
        private readonly FirestoreService $firestore,
        private readonly FirestoreUserService $firestoreUsers,
    ) {}

    /**
     * GET /api/admin/users
     * Liste tous les utilisateurs avec leurs cartes
     */
    public function listUsers(): JsonResponse
    {
        $users = $this->firestore->list('users');
        
        $usersWithCards = [];
        
        foreach ($users as $user) {
            // Récupérer les cartes de chaque utilisateur
            $cards = $this->firestore->subList('users', $user['id'], 'fuel_cards');
            
            $usersWithCards[] = [
                'id' => $user['id'],
                'name' => $user['name'] ?? '',
                'email' => $user['email'] ?? '',
                'phone' => $user['phone'] ?? null,
                'city' => $user['city'] ?? null,
                'role' => $user['role'] ?? 'user',
                'created_at' => $user['created_at'] ?? null,
                'cards' => $cards,
            ];
        }
        
        return response()->json([
            'users' => $usersWithCards,
        ]);
    }

    /**
     * GET /api/admin/users/{userId}
     * Détails d'un utilisateur avec sa carte
     */
    public function showUser(string $userId): JsonResponse
    {
        $user = $this->firestoreUsers->findById($userId);
        
        if (!$user) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }
        
        // Récupérer les cartes de l'utilisateur
        $cards = $this->firestore->subList('users', $userId, 'fuel_cards');
        
        // Récupérer les plans disponibles
        $plans = $this->firestore->list('card_plans');
        
        return response()->json([
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'] ?? '',
                'email' => $user['email'] ?? '',
                'phone' => $user['phone'] ?? null,
                'city' => $user['city'] ?? null,
                'role' => $user['role'] ?? 'user',
                'created_at' => $user['created_at'] ?? null,
            ],
            'cards' => $cards,
            'available_plans' => $plans,
        ]);
    }

    /**
     * PUT /api/admin/users/{userId}/card-level
     * Modifier le niveau de carte d'un utilisateur
     */
    public function updateCardLevel(Request $request, string $userId): JsonResponse
    {
        $request->validate([
            'plan_id' => 'required|string|in:bronze,silver,gold',
        ]);
        
        // Vérifier que l'utilisateur existe
        $user = $this->firestoreUsers->findById($userId);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        
        // Récupérer la carte de l'utilisateur
        $cards = $this->firestore->subList('users', $userId, 'fuel_cards');
        if (empty($cards)) {
            return response()->json(['message' => 'User has no fuel card'], 404);
        }
        
        $card = $cards[0]; // Première carte
        
        // Récupérer le plan sélectionné
        $plan = $this->firestore->get('card_plans', $request->plan_id);
        if (!$plan) {
            return response()->json(['message' => 'Card plan not found'], 404);
        }
        
        // Mettre à jour la carte avec le nouveau plan
        $updated = $this->firestore->subUpdate(
            'users',
            $userId,
            'fuel_cards',
            $card['id'],
            [
                'card_plan_id' => $plan['id'],
                'card_plan_name' => $plan['name'],
                'color' => $plan['color'],
                'authorized_products' => $plan['authorized_products'],
            ]
        );
        
        return response()->json([
            'message' => "Card level updated to {$plan['name']} successfully",
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
            ],
            'card' => $updated,
            'plan' => [
                'id' => $plan['id'],
                'name' => $plan['name'],
                'tier_level' => $plan['tier_level'],
                'color' => $plan['color'],
            ],
        ]);
    }
}
