<?php
/**
 * Créer ou réinitialiser un utilisateur de test
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$email = 'test@example.com';
$password = 'password123';
$name = 'Test User';

echo "=== Création/Mise à jour utilisateur de test ===\n\n";

$user = User::where('email', $email)->first();

if ($user) {
    echo "Utilisateur existant trouvé (ID: {$user->id})\n";
    echo "Mise à jour du mot de passe...\n";
    $user->password = Hash::make($password);
    $user->save();
    echo "✓ Mot de passe mis à jour!\n\n";
} else {
    echo "Création d'un nouvel utilisateur...\n";
    $user = User::create([
        'name' => $name,
        'email' => $email,
        'password' => Hash::make($password),
    ]);
    echo "✓ Utilisateur créé (ID: {$user->id})!\n\n";
}

echo "Informations de connexion:\n";
echo "  Email: {$email}\n";
echo "  Password: {$password}\n\n";

echo "Testez maintenant avec:\n";
echo "  php test-login.php {$email} {$password}\n";
