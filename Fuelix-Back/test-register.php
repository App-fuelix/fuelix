<?php
$baseUrl = 'http://localhost:8000/api';

echo "=== Test Register ===\n\n";

// Test 1: Register un nouvel utilisateur
echo "Test 1: Créer un nouvel utilisateur...\n";
$ch = curl_init("{$baseUrl}/register");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
    CURLOPT_POSTFIELDS => json_encode([
        'name'                  => 'Nouveau User',
        'email'                 => 'nouveau@fuelix.com',
        'password'              => 'password123',
        'password_confirmation' => 'password123',
    ])
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Response Code: {$httpCode}\n";
if ($httpCode === 201) {
    $data = json_decode($response, true);
    echo "✓ Utilisateur créé!\n";
    echo "  - Name: {$data['user']['name']}\n";
    echo "  - Email: {$data['user']['email']}\n";
    echo "  - Token: " . substr($data['token'], 0, 30) . "...\n\n";
} else {
    echo "✗ Erreur: {$response}\n\n";
}

// Test 2: Email déjà utilisé
echo "Test 2: Email déjà utilisé (doit retourner 422)...\n";
$ch = curl_init("{$baseUrl}/register");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
    CURLOPT_POSTFIELDS => json_encode([
        'name'                  => 'Duplicate',
        'email'                 => 'nouveau@fuelix.com',
        'password'              => 'password123',
        'password_confirmation' => 'password123',
    ])
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Response Code: {$httpCode}\n";
echo $httpCode === 422 ? "✓ Email dupliqué correctement rejeté\n\n" : "✗ Devrait retourner 422\n\n";

// Test 3: Mot de passe trop court
echo "Test 3: Mot de passe trop court (doit retourner 422)...\n";
$ch = curl_init("{$baseUrl}/register");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
    CURLOPT_POSTFIELDS => json_encode([
        'name'                  => 'Test',
        'email'                 => 'test2@fuelix.com',
        'password'              => '123',
        'password_confirmation' => '123',
    ])
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Response Code: {$httpCode}\n";
echo $httpCode === 422 ? "✓ Mot de passe trop court correctement rejeté\n" : "✗ Devrait retourner 422\n";

echo "\n=== Tests terminés ===\n";
