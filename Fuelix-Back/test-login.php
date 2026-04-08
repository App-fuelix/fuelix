<?php
/**
 * Test du système de login
 * Usage: php test-login.php [email] [password]
 */

$baseUrl = 'http://localhost:8000/api';
$email = $argv[1] ?? 'test@example.com';
$password = $argv[2] ?? 'eyaeyaeya';

echo "=== Test de Login - Fuelix ===\n\n";
echo "Email: {$email}\n";
echo "Password: {$password}\n\n";

// Test 1: Login
echo "Test 1: Tentative de connexion...\n";
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

echo "Response Code: {$httpCode}\n";

if ($httpCode === 200) {
    $data = json_decode($response, true);
    echo "✓ Login réussi!\n\n";
    echo "User Info:\n";
    echo "  - ID: {$data['user']['id']}\n";
    echo "  - Name: {$data['user']['name']}\n";
    echo "  - Email: {$data['user']['email']}\n\n";
    echo "Token: " . substr($data['token'], 0, 50) . "...\n\n";
    
    $token = $data['token'];
    
    // Test 2: Vérifier le token avec /me
    echo "Test 2: Vérification du token avec /api/me...\n";
    $ch = curl_init("{$baseUrl}/me");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ]
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $user = json_decode($response, true);
        echo "✓ Token valide!\n";
        echo "User authentifié: {$user['email']}\n\n";
        
        // Test 3: Logout
        echo "Test 3: Déconnexion...\n";
        $ch = curl_init("{$baseUrl}/logout");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            echo "✓ Déconnexion réussie!\n\n";
            
            // Test 4: Vérifier que le token est invalidé
            echo "Test 4: Vérification que le token est invalidé...\n";
            $ch = curl_init("{$baseUrl}/me");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $token
                ]
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 401) {
                echo "✓ Token correctement invalidé!\n\n";
                echo "=== TOUS LES TESTS RÉUSSIS ===\n";
            } else {
                echo "✗ Le token devrait être invalidé (attendu 401, reçu {$httpCode})\n";
            }
        } else {
            echo "✗ Échec de la déconnexion\n";
            echo "Response: {$response}\n";
        }
    } else {
        echo "✗ Token invalide\n";
        echo "Response: {$response}\n";
    }
    
} else {
    echo "✗ Échec de connexion\n";
    $data = json_decode($response, true);
    echo "Message: " . ($data['message'] ?? 'Erreur inconnue') . "\n\n";
    
    echo "Suggestions:\n";
    echo "- Vérifiez que l'email existe dans la base de données\n";
    echo "- Vérifiez le mot de passe\n";
    echo "- Pour tester: php test-login.php test@example.com password123\n";
}
