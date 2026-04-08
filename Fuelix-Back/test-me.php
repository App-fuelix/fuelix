<?php
/**
 * Test de l'endpoint /api/me
 * Usage: php test-me.php
 */

$baseUrl = 'http://localhost:8000/api';
$email = 'test@example.com';
$password = 'eyaeyaeya';

echo "=== Test de l'endpoint /api/me ===\n\n";

// Étape 1: Se connecter pour obtenir un token
echo "Étape 1: Connexion pour obtenir un token...\n";
$ch = curl_init("{$baseUrl}/login");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode([
        'email' => $email,
        'password' => $password
    ])
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    die("✗ Échec de connexion\n");
}

$data = json_decode($response, true);
$token = $data['token'];
echo "✓ Token obtenu: " . substr($token, 0, 30) . "...\n\n";

// Étape 2: Tester /api/me avec le token
echo "Étape 2: Appel de /api/me avec le token...\n";
$ch = curl_init("{$baseUrl}/me");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token,
        'Accept: application/json'
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Response Code: {$httpCode}\n";
echo "Response:\n";
echo json_encode(json_decode($response), JSON_PRETTY_PRINT) . "\n\n";

if ($httpCode === 200) {
    $user = json_decode($response, true);
    echo "✓ /api/me fonctionne parfaitement!\n\n";
    echo "Informations utilisateur:\n";
    echo "  - ID: {$user['id']}\n";
    echo "  - Name: {$user['name']}\n";
    echo "  - Email: {$user['email']}\n";
    echo "  - Created: {$user['created_at']}\n";
} else {
    echo "✗ Erreur lors de l'appel à /api/me\n";
}

// Étape 3: Tester sans token (doit échouer)
echo "\nÉtape 3: Test sans token (doit retourner 401)...\n";
$ch = curl_init("{$baseUrl}/me");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json'
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Response Code: {$httpCode}\n";
if ($httpCode === 401) {
    echo "✓ Correctement protégé - 401 sans token\n";
} else {
    echo "✗ Devrait retourner 401 sans token\n";
}

// Étape 4: Tester avec un mauvais token
echo "\nÉtape 4: Test avec un token invalide (doit retourner 401)...\n";
$ch = curl_init("{$baseUrl}/me");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer invalid_token_12345',
        'Accept: application/json'
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Response Code: {$httpCode}\n";
if ($httpCode === 401) {
    echo "✓ Correctement protégé - 401 avec token invalide\n";
} else {
    echo "✗ Devrait retourner 401 avec token invalide\n";
}

echo "\n=== FIN DES TESTS ===\n";
