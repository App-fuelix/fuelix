<?php
$baseUrl = 'http://localhost:8000/api';
$email = 'test@example.com';
$password = 'eyaeyaeya';

echo "=== Test Dashboard Fuelix ===\n\n";

// 1. Login
echo "1. Login...\n";
$ch = curl_init("{$baseUrl}/login");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode(['email' => $email, 'password' => $password])
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    die("✗ Login failed\n");
}

$data = json_decode($response, true);
$token = $data['token'];
echo "✓ Token: " . substr($token, 0, 30) . "...\n\n";

// 2. Get Dashboard
echo "2. Récupération du Dashboard...\n";
$ch = curl_init("{$baseUrl}/dashboard");
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

if ($httpCode === 200) {
    $dashboard = json_decode($response, true);
    echo "✓ Dashboard récupéré!\n\n";
    echo json_encode($dashboard, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
} else {
    echo "✗ Erreur\n";
    echo "Response: {$response}\n";
}
