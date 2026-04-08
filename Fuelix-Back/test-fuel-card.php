<?php
$baseUrl = 'http://localhost:8000/api';
$email = 'test@example.com';
$password = 'eyaeyaeya';

echo "=== Test FuelCard API ===\n\n";

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
echo "✓ Token obtenu\n\n";

// 2. Afficher la carte
echo "2. Afficher la carte principale...\n";
$ch = curl_init("{$baseUrl}/fuel-cards/show");
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
    $card = json_decode($response, true);
    echo "✓ Carte récupérée!\n";
    echo json_encode($card, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    $cardId = $card['id'];
    $currentBalance = $card['balance_raw'];
} else {
    echo "✗ Erreur: {$response}\n\n";
    die();
}

// 3. Lister toutes les cartes
echo "3. Lister toutes les cartes...\n";
$ch = curl_init("{$baseUrl}/fuel-cards");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token,
        'Accept: application/json'
    ]
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $cards = json_decode($response, true);
    echo "✓ " . count($cards['cards']) . " carte(s) trouvée(s)\n\n";
} else {
    echo "✗ Erreur\n\n";
}

// 4. Recharger la carte
echo "4. Recharger la carte de 100 TND...\n";
$ch = curl_init("{$baseUrl}/fuel-cards/recharge");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token,
        'Accept: application/json'
    ],
    CURLOPT_POSTFIELDS => json_encode(['amount' => 100])
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Response Code: {$httpCode}\n";
if ($httpCode === 200) {
    $result = json_decode($response, true);
    echo "✓ Recharge réussie!\n";
    echo "Ancien solde: {$currentBalance} TND\n";
    echo "Nouveau solde: {$result['new_balance']}\n\n";
} else {
    echo "✗ Erreur: {$response}\n\n";
}

// 5. Historique des transactions
echo "5. Historique des transactions...\n";
$ch = curl_init("{$baseUrl}/fuel-cards/transactions");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token,
        'Accept: application/json'
    ]
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    echo "✓ {$data['total_count']} transaction(s) au total\n";
    echo "Dernières transactions:\n";
    foreach (array_slice($data['transactions'], 0, 5) as $transaction) {
        echo "  - {$transaction['date']}: {$transaction['quantity_liters']} à {$transaction['station_name']} = {$transaction['amount']}\n";
    }
} else {
    echo "✗ Erreur\n";
}

echo "\n=== Tests terminés ===\n";
