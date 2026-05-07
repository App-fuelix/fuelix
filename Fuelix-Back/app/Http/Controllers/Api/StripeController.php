<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FirestoreService;
use App\Services\FirestoreUserService;
use Illuminate\Http\Request;
use Stripe\StripeClient;

class StripeController extends Controller
{
    public function __construct(
        private readonly FirestoreService $firestore,
        private readonly FirestoreUserService $firestoreUsers,
    ) {}

    private function stripe(): StripeClient
    {
        return new StripeClient((string) config('services.stripe.secret', ''));
    }

    private function getFirestoreUid(Request $request): ?string
    {
        $user = $this->firestoreUsers->findByEmail($request->user()->email);
        return $user['id'] ?? null;
    }

    /**
     * POST /api/stripe/create-payment-intent
     */
    public function createPaymentIntent(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1|max:10000',
        ]);

        $secret = config('services.stripe.secret', '');
        if (empty($secret)) {
            return response()->json(['message' => 'Stripe is not configured.'], 503);
        }

        try {
            $intent = $this->stripe()->paymentIntents->create([
                'amount'   => (int) round((float) $request->amount * 100), // cents
                'currency' => 'eur',
                'automatic_payment_methods' => ['enabled' => true],
            ]);

            return response()->json([
                'client_secret' => $intent->client_secret,
                'payment_intent_id' => $intent->id,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * POST /api/stripe/confirm-recharge
     */
    public function confirmRecharge(Request $request)
    {
        $request->validate([
            'payment_intent_id' => 'required|string',
            'amount'            => 'required|numeric|min:1',
        ]);

        $secret = config('services.stripe.secret', '');
        if (empty($secret)) {
            return response()->json(['message' => 'Stripe is not configured.'], 503);
        }

        try {
            $intent = $this->stripe()->paymentIntents->retrieve($request->payment_intent_id);

            if ($intent->status !== 'succeeded') {
                return response()->json([
                    'message' => 'Payment not completed. Status: ' . $intent->status,
                ], 422);
            }

            // Credit the user's fuel card
            $uid = $this->getFirestoreUid($request);
            if (!$uid) {
                return response()->json(['message' => 'User not found'], 404);
            }

            $cards = $this->firestore->subList('users', $uid, 'fuel_cards');
            if (empty($cards)) {
                return response()->json(['message' => 'No fuel card found'], 404);
            }

            $card = $cards[0];
            $newBalance = (float) ($card['balance'] ?? 0) + (float) $request->amount;

            $this->firestore->subUpdate('users', $uid, 'fuel_cards', $card['id'], [
                'balance' => $newBalance,
            ]);

            return response()->json([
                'message'     => 'Recharge successful',
                'new_balance' => number_format($newBalance, 2) . ' TND',
                'balance_raw' => $newBalance,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
