<?php

/**
 * Seed fake fuel card + transactions into Firestore for a given user email.
 * Run: php seed-firestore.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\FirestoreService;
use App\Services\FirestoreUserService;
use Carbon\Carbon;

$email = readline("Enter user email to seed: ");
$email = trim($email);

/** @var FirestoreUserService $users */
$users = app(FirestoreUserService::class);

/** @var FirestoreService $firestore */
$firestore = app(FirestoreService::class);

$user = $users->findByEmail($email);

if (!$user) {
    echo "User not found: {$email}\n";
    exit(1);
}

$uid = $user['id'];
echo "Found user: {$user['name']} (uid: {$uid})\n";

// -------------------------------------------------------------------------
// 1. Create a fuel card
// -------------------------------------------------------------------------
$card = $firestore->subCreate('users', $uid, 'fuel_cards', [
    'card_number'  => '4111111111111234',
    'issuer'       => 'Fuelix',
    'expiry_month' => '12',
    'expiry_year'  => '2027',
    'balance'      => 250.00,
    'status'       => 'active',
]);

echo "Created fuel card: {$card['id']}\n";

// -------------------------------------------------------------------------
// 2. Create a vehicle
// -------------------------------------------------------------------------
$vehicle = $firestore->subCreate('users', $uid, 'vehicles', [
    'plate_number'        => '123 TUN 4567',
    'model'               => 'Volkswagen Golf',
    'fuel_type'           => 'Diesel',
    'average_consumption' => 6.5,
]);

echo "Created vehicle: {$vehicle['id']}\n";

// -------------------------------------------------------------------------
// 3. Create fake transactions (last 30 days)
// -------------------------------------------------------------------------
$stations = ['Shell Tunis', 'Total Ariana', 'Agil Sousse', 'Shell Sfax', 'Total Nabeul'];

$transactions = [
    // This week
    ['days_ago' => 1, 'liters' => 35.0, 'price' => 2.150, 'station' => 'Shell Tunis'],
    ['days_ago' => 3, 'liters' => 28.5, 'price' => 2.150, 'station' => 'Total Ariana'],
    ['days_ago' => 5, 'liters' => 42.0, 'price' => 2.150, 'station' => 'Agil Sousse'],
    // Last week
    ['days_ago' => 8, 'liters' => 30.0, 'price' => 2.150, 'station' => 'Shell Sfax'],
    ['days_ago' => 10, 'liters' => 25.0, 'price' => 2.150, 'station' => 'Total Nabeul'],
    ['days_ago' => 12, 'liters' => 38.0, 'price' => 2.150, 'station' => 'Shell Tunis'],
    // This month (older)
    ['days_ago' => 15, 'liters' => 45.0, 'price' => 2.150, 'station' => 'Total Ariana'],
    ['days_ago' => 18, 'liters' => 32.0, 'price' => 2.150, 'station' => 'Agil Sousse'],
    ['days_ago' => 22, 'liters' => 29.0, 'price' => 2.150, 'station' => 'Shell Tunis'],
    // Last month
    ['days_ago' => 35, 'liters' => 40.0, 'price' => 2.100, 'station' => 'Shell Sfax'],
    ['days_ago' => 38, 'liters' => 33.0, 'price' => 2.100, 'station' => 'Total Nabeul'],
    ['days_ago' => 42, 'liters' => 27.0, 'price' => 2.100, 'station' => 'Shell Tunis'],
];

foreach ($transactions as $tx) {
    $date = Carbon::now()->subDays($tx['days_ago'])->toIso8601String();
    $amount = round($tx['liters'] * $tx['price'], 2);

    $created = $firestore->subCreate('users', $uid, 'transactions', [
        'fuel_card_id'    => $card['id'],
        'vehicle_id'      => $vehicle['id'],
        'date'            => $date,
        'amount'          => $amount,
        'quantity_liters' => $tx['liters'],
        'price_per_liter' => $tx['price'],
        'station_name'    => $tx['station'],
    ]);

    echo "Created transaction: {$created['id']} — {$tx['liters']}L @ {$tx['station']}\n";
}

echo "\nDone! {$uid} now has 1 card, 1 vehicle, and " . count($transactions) . " transactions in Firestore.\n";
