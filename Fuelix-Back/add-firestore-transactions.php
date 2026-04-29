<?php

/**
 * Append realistic Firestore transactions without deleting existing data.
 *
 * Usage:
 *   php add-firestore-transactions.php
 *   php add-firestore-transactions.php user@example.com
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\FirestoreService;
use App\Services\FirestoreUserService;
use Carbon\Carbon;

/** @var FirestoreService $firestore */
$firestore = app(FirestoreService::class);

/** @var FirestoreUserService $users */
$users = app(FirestoreUserService::class);

$email = trim((string) ($argv[1] ?? ''));
$user = null;

if ($email !== '') {
    $user = $users->findByEmail($email);
    if (!$user) {
        echo "User not found: {$email}\n";
        exit(1);
    }
} else {
    $allUsers = $firestore->list('users');
    if (empty($allUsers)) {
        echo "No Firestore users found.\n";
        exit(1);
    }

    $user = $allUsers[0];
    $email = (string) ($user['email'] ?? '');
}

$uid = (string) ($user['id'] ?? '');
if ($uid === '') {
    echo "Selected user has no Firestore id.\n";
    exit(1);
}

echo "Adding transactions for {$email} ({$uid})...\n";

$vehicles = $firestore->subList('users', $uid, 'vehicles');
if (empty($vehicles)) {
    $vehicles[] = $firestore->subCreate('users', $uid, 'vehicles', [
        'plate_number' => '999 TUN 1000',
        'model' => 'FueliX Demo Car',
        'fuel_type' => 'Essence',
        'average_consumption' => 7.2,
    ]);
    echo "Created demo vehicle.\n";
}

$cards = $firestore->subList('users', $uid, 'fuel_cards');
if (empty($cards)) {
    $cards[] = $firestore->subCreate('users', $uid, 'fuel_cards', [
        'card_number' => '4111111111119999',
        'issuer' => 'Fuelix',
        'expiry_month' => '12',
        'expiry_year' => '2027',
        'balance' => 850.00,
        'status' => 'active',
        'authorized_products' => json_encode(['fuel', 'carwash', 'lubricants']),
    ]);
    echo "Created demo fuel card.\n";
}

$card = $cards[0];
$stations = [
    'Shell Tunis Centre',
    'Total Ariana',
    'Agil Sousse',
    'Shell Sfax',
    'Total Nabeul',
    'Agil Tunis',
];

$today = Carbon::now();
$daysThisMonth = [2, 5, 8, 11, 15, 18, 22, 25, 28];
$daysThisMonth = array_values(array_filter($daysThisMonth, fn ($day) => $day <= $today->day));
if (empty($daysThisMonth)) {
    $daysThisMonth = [$today->day];
}

$rows = [];
foreach ($daysThisMonth as $index => $day) {
    $liters = [24.5, 31.0, 18.7, 36.2, 42.0, 27.8, 33.4, 21.6, 39.1][$index % 9];
    $price = [2.150, 2.180, 2.120, 2.200, 2.140][$index % 5];
    $rows[] = [
        'date' => $today->copy()->day($day)->setTime(9 + ($index % 8), 15)->toIso8601String(),
        'liters' => $liters,
        'price' => $price,
        'station' => $stations[$index % count($stations)],
        'vehicle' => $vehicles[$index % count($vehicles)],
        'products' => ['fuel'],
    ];
}

for ($i = 0; $i < 4; $i++) {
    $previous = $today->copy()->subMonth();
    $safeDay = min([4, 11, 18, 25][$i], $previous->daysInMonth);
    $liters = [29.0, 34.5, 23.2, 37.4][$i];
    $price = 2.100;
    $rows[] = [
        'date' => $previous->day($safeDay)->setTime(10 + $i, 30)->toIso8601String(),
        'liters' => $liters,
        'price' => $price,
        'station' => $stations[($i + 2) % count($stations)],
        'vehicle' => $vehicles[$i % count($vehicles)],
        'products' => ['fuel'],
    ];
}

$totalAmount = 0.0;
foreach ($rows as $row) {
    $amount = round((float) $row['liters'] * (float) $row['price'], 2);
    $totalAmount += $amount;

    $created = $firestore->subCreate('users', $uid, 'transactions', [
        'fuel_card_id' => $card['id'],
        'vehicle_id' => $row['vehicle']['id'],
        'date' => $row['date'],
        'amount' => $amount,
        'quantity_liters' => (float) $row['liters'],
        'price_per_liter' => (float) $row['price'],
        'station_name' => $row['station'],
        'authorized_products' => json_encode($row['products']),
    ]);

    echo "  Added {$created['id']} | {$row['date']} | {$row['liters']} L | {$amount} TND\n";
}

$oldBalance = (float) ($card['balance'] ?? 0);
$newBalance = max(0, $oldBalance - $totalAmount);
$firestore->subUpdate('users', $uid, 'fuel_cards', $card['id'], [
    'balance' => round($newBalance, 2),
]);

echo "\nAdded " . count($rows) . " transactions.\n";
echo "Card balance updated: " . number_format($oldBalance, 2) . " TND -> " . number_format($newBalance, 2) . " TND\n";
