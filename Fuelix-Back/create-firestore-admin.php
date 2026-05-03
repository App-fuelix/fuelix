<?php

/**
 * Create or update a Firestore admin user for the web admin dashboard.
 *
 * Usage:
 *   php create-firestore-admin.php admin@example.com password123 "Admin Name"
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Services\FirestoreUserService;
use Illuminate\Support\Facades\Hash;

$email = strtolower(trim($argv[1] ?? ''));
$password = (string) ($argv[2] ?? '');
$name = trim($argv[3] ?? 'FueliX Admin');

if ($email === '' || $password === '') {
    echo "Usage:\n";
    echo "  php create-firestore-admin.php admin@example.com password123 \"Admin Name\"\n";
    exit(1);
}

if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "Invalid email: {$email}\n";
    exit(1);
}

if (strlen($password) < 8) {
    echo "Password must be at least 8 characters.\n";
    exit(1);
}

/** @var FirestoreUserService $firestoreUsers */
$firestoreUsers = app(FirestoreUserService::class);
$passwordHash = Hash::make($password);

echo "=== FueliX Firestore Admin Setup ===\n\n";

$existing = $firestoreUsers->findByEmail($email);

if ($existing) {
    echo "Firestore user found: {$email}\n";
    echo "Updating password and role...\n";

    $admin = $firestoreUsers->updateUser($existing['id'], [
        'name' => $name,
        'email' => $email,
        'password' => $passwordHash,
        'role' => 'admin',
    ]);
} else {
    echo "Creating Firestore admin user: {$email}\n";

    $admin = $firestoreUsers->createUser($name, $email, $password);
    $admin = $firestoreUsers->updateUser($admin['id'], [
        'role' => 'admin',
    ]);
}

User::updateOrCreate(
    ['email' => $email],
    [
        'name' => $name,
        'password' => $passwordHash,
    ]
);

echo "\nAdmin ready.\n";
echo "Email: {$email}\n";
echo "Role: " . ($admin['role'] ?? 'admin') . "\n";
echo "Login URL: /login\n";
