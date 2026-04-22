<?php

/**
 * Clean + seed Firestore for a given user.
 * Creates: 2 vehicles, 1 fuel card, 12 transactions with varied authorized products.
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
if (!$user) { echo "User not found: {$email}\n"; exit(1); }

$uid = $user['id'];
echo "Found user: {$user['name']} (uid: {$uid})\n";

// -------------------------------------------------------------------------
// 1. Clean existing data
// -------------------------------------------------------------------------
echo "\nCleaning existing data...\n";

foreach ($firestore->subList('users', $uid, 'transactions') as $t) {
    $firestore->subDelete('users', $uid, 'transactions', $t['id']);
    echo "  Deleted transaction: {$t['id']}\n";
}

foreach ($firestore->subList('users', $uid, 'fuel_cards') as $c) {
    $firestore->subDelete('users', $uid, 'fuel_cards', $c['id']);
    echo "  Deleted card: {$c['id']}\n";
}

foreach ($firestore->subList('users', $uid, 'vehicles') as $v) {
    $firestore->subDelete('users', $uid, 'vehicles', $v['id']);
    echo "  Deleted vehicle: {$v['id']}\n";
}

// -------------------------------------------------------------------------
// 2. Create 2 vehicles
// -------------------------------------------------------------------------
echo "\nCreating vehicles...\n";

$golf = $firestore->subCreate('users', $uid, 'vehicles', [
    'plate_number'        => '123 TUN 4567',
    'model'               => 'Volkswagen Golf',
    'fuel_type'           => 'Diesel',
    'average_consumption' => 6.5,
]);
echo "  Created: Volkswagen Golf ({$golf['id']})\n";

$bmw = $firestore->subCreate('users', $uid, 'vehicles', [
    'plate_number'        => '456 TUN 7890',
    'model'               => 'BMW Serie 3',
    'fuel_type'           => 'Essence',
    'average_consumption' => 8.2,
]);
echo "  Created: BMW Serie 3 ({$bmw['id']})\n";

// -------------------------------------------------------------------------
// 3. Create 1 fuel card
// -------------------------------------------------------------------------
echo "\nCreating fuel card...\n";

$card = $firestore->subCreate('users', $uid, 'fuel_cards', [
    'card_number'         => '4111111111111234',
    'issuer'              => 'Fuelix',
    'expiry_month'        => '12',
    'expiry_year'         => '2027',
    'balance'             => 850.00,
    'status'              => 'active',
    'authorized_products' => json_encode(['fuel', 'carwash', 'lubricants']),
]);
echo "  Created card: {$card['id']}\n";

// -------------------------------------------------------------------------
// 4. Create 12 transactions with varied products and vehicles
// -------------------------------------------------------------------------
echo "\nCreating transactions...\n";

$transactions = [
    // This week — Golf
    [
        'days_ago' => 1, 'liters' => 35.0, 'price' => 2.150,
        'station' => 'Shell Tunis Centre', 'vehicle' => $golf,
        'products' => ['fuel'],
    ],
    [
        'days_ago' => 2, 'liters' => 0, 'price' => 0,
        'station' => 'Total Ariana', 'vehicle' => $golf,
        'products' => ['carwash'], 'amount_override' => 15.0,
    ],
    [
        'days_ago' => 3, 'liters' => 28.5, 'price' => 2.150,
        'station' => 'Agil Sousse', 'vehicle' => $bmw,
        'products' => ['fuel'],
    ],
    // Last week — BMW
    [
        'days_ago' => 8, 'liters' => 42.0, 'price' => 2.200,
        'station' => 'Shell Sfax', 'vehicle' => $bmw,
        'products' => ['fuel', 'lubricants'],
    ],
    [
        'days_ago' => 10, 'liters' => 0, 'price' => 0,
        'station' => 'Total Nabeul', 'vehicle' => $golf,
        'products' => ['carwash', 'lubricants'], 'amount_override' => 35.0,
    ],
    [
        'days_ago' => 12, 'liters' => 38.0, 'price' => 2.150,
        'station' => 'Shell Tunis Nord', 'vehicle' => $golf,
        'products' => ['fuel'],
    ],
    // This month (older) — mixed
    [
        'days_ago' => 15, 'liters' => 45.0, 'price' => 2.150,
        'station' => 'Total Ariana', 'vehicle' => $bmw,
        'products' => ['fuel'],
    ],
    [
        'days_ago' => 18, 'liters' => 32.0, 'price' => 2.150,
        'station' => 'Agil Tunis', 'vehicle' => $golf,
        'products' => ['fuel', 'carwash'],
    ],
    [
        'days_ago' => 22, 'liters' => 0, 'price' => 0,
        'station' => 'Shell Manouba', 'vehicle' => $bmw,
        'products' => ['lubricants'], 'amount_override' => 22.0,
    ],
    // Last month
    [
        'days_ago' => 35, 'liters' => 40.0, 'price' => 2.100,
        'station' => 'Shell Sfax', 'vehicle' => $golf,
        'products' => ['fuel'],
    ],
    [
        'days_ago' => 38, 'liters' => 33.0, 'price' => 2.100,
        'station' => 'Total Nabeul', 'vehicle' => $bmw,
        'products' => ['fuel', 'lubricants'],
    ],
    [
        'days_ago' => 42, 'liters' => 27.0, 'price' => 2.100,
        'station' => 'Agil Sousse', 'vehicle' => $golf,
        'products' => ['fuel', 'carwash', 'lubricants'],
    ],
];

foreach ($transactions as $tx) {
    $date   = Carbon::now()->subDays($tx['days_ago'])->toIso8601String();
    $liters = $tx['liters'];
    $price  = $tx['price'];
    $amount = isset($tx['amount_override'])
        ? $tx['amount_override']
        : round($liters * $price, 2);

    $created = $firestore->subCreate('users', $uid, 'transactions', [
        'fuel_card_id'         => $card['id'],
        'vehicle_id'           => $tx['vehicle']['id'],
        'date'                 => $date,
        'amount'               => $amount,
        'quantity_liters'      => $liters,
        'price_per_liter'      => $price,
        'station_name'         => $tx['station'],
        'authorized_products'  => json_encode($tx['products']),
    ]);

    $productsStr = implode(', ', $tx['products']);
    $vehicleModel = $tx['vehicle']['model'];
    echo "  TX {$created['id']} — {$vehicleModel} — {$tx['station']} — [{$productsStr}] — {$amount} TND\n";
}

echo "\nDone! Seeded 2 vehicles, 1 card, 12 transactions for {$user['name']}.\n";
