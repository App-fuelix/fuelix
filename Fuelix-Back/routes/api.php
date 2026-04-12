<?php
use App\Http\Controllers\Api\AuthController;
use App\Services\FirestoreUserService;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class,'login']);

// Firebase Auth routes
Route::post('/firebase/register', [AuthController::class, 'firebaseRegister']);
Route::post('/firebase/login', [AuthController::class, 'firebaseLogin']);

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class,'logout']);
    Route::get('/me', [AuthController::class,'me']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::put('/change-password', [AuthController::class, 'changePassword']);

});

Route::post('/forgot-password', function (Request $request, FirestoreUserService $firestore) {
    $request->validate(['email' => 'required|email']);

    // Ensure the user exists in MySQL (shadow copy for Sanctum/Password broker)
    $firestoreUser = $firestore->findByEmail($request->email);
    if ($firestoreUser) {
        $user = \App\Models\User::firstOrNew(['email' => $firestoreUser['email']]);
        $user->name = $firestoreUser['name'];
        $user->password = $firestoreUser['password'];
        $user->save();
    }

    $status = Password::sendResetLink(
        $request->only('email')
    );

    return response()->json([
        'message' => $status === Password::RESET_LINK_SENT
            ? 'Lien de réinitialisation envoyé par email'
            : 'Impossible d\'envoyer le lien de réinitialisation',
        'status' => $status
    ]);
})->name('password.email');

Route::post('/reset-password', function (Request $request, FirestoreUserService $firestore) {
    $request->validate([
        'token'                 => ['required'],
        'email'                 => ['required', 'email'],
        'password'              => ['required', 'min:8', 'confirmed'],
        'password_confirmation' => ['required'],
    ]);

    $status = Password::reset(
        $request->only('email', 'password', 'password_confirmation', 'token'),
        function ($user, $password) use ($firestore) {
            $newHash = Hash::make($password);

            // Update MySQL
            $user->forceFill(['password' => $newHash])
                 ->setRememberToken(Str::random(60));
            $user->save();

            // Update Firestore (source of truth for auth)
            $firestoreUser = $firestore->findByEmail($user->email);
            if ($firestoreUser) {
                $firestore->updateUser($firestoreUser['id'], ['password' => $newHash]);
            }

            // Invalidate all tokens
            $user->tokens()->delete();
        }
    );

    return $status === Password::PASSWORD_RESET
        ? response()->json([
            'message' => 'Mot de passe réinitialisé avec succès. Vous pouvez maintenant vous connecter.',
        ], 200)
        : response()->json([
            'message' => __($status),
            'error'   => true
        ], 400);
})->name('password.reset');
Route::get('/test', function () {
    return response()->json(["ok" => true]);
});

Route::get('/firestore/health', function (FirestoreUserService $firestore) {
    try {
        $firestore->findByEmail('__healthcheck__@invalid.local');

        return response()->json([
            'ok' => true,
            'message' => 'Firestore REST connection is healthy',
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'ok' => false,
            'message' => 'Firestore REST connection failed',
            'error' => $e->getMessage(),
        ], 500);
    }
});
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\CardController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'home']);
    
    // Fuel Card routes
    Route::prefix('fuel-cards')->group(function () {
        Route::get('/', [CardController::class, 'index']);
        Route::get('/show', [CardController::class, 'show']);
        Route::post('/recharge', [CardController::class, 'recharge']);
        Route::get('/transactions', [CardController::class, 'transactions']);
            Route::get('/history', [CardController::class, 'history']);
    });
});
  

