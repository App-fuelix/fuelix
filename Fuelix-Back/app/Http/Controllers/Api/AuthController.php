<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FirestoreUserService;
use App\Services\FirebaseTokenService;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        private readonly FirestoreUserService $firestoreUsers,
        private readonly FirebaseTokenService $firebaseTokens,
    ) {
    }

    // REGISTER
    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email',
            'password' => 'required|min:8|confirmed',
            'phone'    => 'nullable|string|max:20',
            'city'     => 'nullable|string|max:100',
        ]);

        $existingFirestoreUser = $this->firestoreUsers->findByEmail($request->email);

        if ($existingFirestoreUser) {
            throw ValidationException::withMessages([
                'email' => ['This email is already registered.'],
            ]);
        }

        $firestoreUser = $this->firestoreUsers->createUser(
            $request->name,
            $request->email,
            $request->password,
            $request->input('phone'),
            $request->input('city'),
        );

        // Keep a local user for Sanctum token generation used by existing middleware.
        $user = User::firstOrNew(['email' => $firestoreUser['email']]);
        $user->name = $firestoreUser['name'];
        $user->password = $firestoreUser['password'];
        $user->save();

        $token = $user->createToken('fuelix-token')->plainTextToken;

        unset($firestoreUser['password']);

        return response()->json([
            'user'  => $firestoreUser,
            'token' => $token
        ], 201);
    }

    // LOGIN
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $firestoreUser = $this->firestoreUsers->verifyCredentials($request->email, $request->password);

        if (! $firestoreUser) {
            return response()->json([
                'message' => 'Email or password incorrect'
            ], 401);
        }

        $user = User::firstOrNew(['email' => $firestoreUser['email']]);
        $user->name = $firestoreUser['name'];
        $user->password = $firestoreUser['password'];
        $user->save();

        $token = $user->createToken('fuelix-token')->plainTextToken;

        unset($firestoreUser['password']);

        return response()->json([
            'user' => $firestoreUser,
            'token' => $token
        ]);
    }

    // LOGOUT
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    // CURRENT USER
    public function me(Request $request)
    {
        $authUser = $request->user();
        $firestoreUser = $this->firestoreUsers->findByEmail($authUser->email);

        if (! $firestoreUser) {
            return response()->json($authUser);
        }

        unset($firestoreUser['password']);

        return response()->json($firestoreUser);
    }

    // UPDATE PROFILE
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name'  => 'sometimes|string|max:255',
            'email' => 'sometimes|email',
        ]);

        $firestoreUser = $this->firestoreUsers->findByEmail($user->email);

        if ($request->filled('email') && strtolower($request->email) !== strtolower($user->email)) {
            $alreadyTaken = $this->firestoreUsers->findByEmail($request->email);

            if ($alreadyTaken) {
                throw ValidationException::withMessages([
                    'email' => ['This email is already registered.'],
                ]);
            }
        }

        if ($firestoreUser) {
            $updatedFirestoreUser = $this->firestoreUsers->updateUser($firestoreUser['id'], [
                'name' => $request->input('name', $firestoreUser['name']),
                'email' => strtolower($request->input('email', $firestoreUser['email'])),
            ]);

            if ($updatedFirestoreUser) {
                $user->update([
                    'name' => $updatedFirestoreUser['name'],
                    'email' => $updatedFirestoreUser['email'],
                ]);

                unset($updatedFirestoreUser['password']);

                return response()->json([
                    'message' => 'Profil mis a jour',
                    'user' => $updatedFirestoreUser,
                ]);
            }
        }

        $user->update($request->only('name', 'email'));

        return response()->json([
            'message' => 'Profil mis à jour',
            'user'    => $user->fresh(),
        ]);
    }

    // CHANGE PASSWORD
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password'         => 'required|min:8|confirmed',
        ]);

        $user = $request->user();
        $firestoreUser = $this->firestoreUsers->findByEmail($user->email);

        $currentHash = $firestoreUser['password'] ?? $user->password;

        if (!Hash::check($request->current_password, $currentHash)) {
            return response()->json(['message' => 'Mot de passe actuel incorrect'], 422);
        }

        $newHash = Hash::make($request->password);

        if ($firestoreUser) {
            $this->firestoreUsers->updateUser($firestoreUser['id'], [
                'password' => $newHash,
            ]);
        }

        $user->update(['password' => $newHash]);

        return response()->json(['message' => 'Mot de passe modifié avec succès']);
    }

    // FIREBASE REGISTER
    public function firebaseRegister(Request $request)
    {
        $request->validate([
            'firebase_token' => 'required|string',
            'name'           => 'required|string|max:255',
            'email'          => 'required|email',
            'phone'          => 'nullable|string|max:20',
            'city'           => 'nullable|string|max:100',
        ]);

        try {
            $payload = $this->firebaseTokens->verifyIdToken($request->firebase_token);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Invalid Firebase token: ' . $e->getMessage()], 401);
        }

        // Store profile in Firestore
        $existingFirestoreUser = $this->firestoreUsers->findByEmail($request->email);

        if (!$existingFirestoreUser) {
            $data = [
                'name'  => $request->name,
                'email' => strtolower($request->email),
                'uid'   => $payload['uid'] ?? $payload['sub'] ?? '',
            ];
            if ($request->filled('phone')) $data['phone'] = $request->phone;
            if ($request->filled('city'))  $data['city']  = $request->city;

            $firestoreUser = $this->firestoreUsers->createUserFromFirebase($data);
        } else {
            $firestoreUser = $existingFirestoreUser;
        }

        $user = User::firstOrNew(['email' => $firestoreUser['email']]);
        $user->name = $firestoreUser['name'];
        $user->password = bcrypt($payload['sub']); // placeholder, auth is via Firebase
        $user->save();

        $token = $user->createToken('fuelix-token')->plainTextToken;

        unset($firestoreUser['password']);

        return response()->json(['user' => $firestoreUser, 'token' => $token], 201);
    }

    // FIREBASE LOGIN
    public function firebaseLogin(Request $request)
    {
        $request->validate(['firebase_token' => 'required|string']);

        try {
            $payload = $this->firebaseTokens->verifyIdToken($request->firebase_token);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Invalid Firebase token: ' . $e->getMessage()], 401);
        }

        $email = $payload['email'] ?? null;

        if (!$email) {
            return response()->json(['message' => 'Email not found in Firebase token'], 401);
        }

        $firestoreUser = $this->firestoreUsers->findByEmail($email);

        $user = User::firstOrNew(['email' => $email]);
        $user->name = $firestoreUser['name'] ?? ($user->name ?: $email);
        $user->password = $user->password ?: bcrypt($payload['sub']);
        $user->save();

        $token = $user->createToken('fuelix-token')->plainTextToken;

        $userData = $firestoreUser ?? ['email' => $email, 'name' => $user->name];
        unset($userData['password']);

        return response()->json(['user' => $userData, 'token' => $token]);
    }
}
