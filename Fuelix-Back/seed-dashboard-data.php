<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Vehicle;
use App\Models\FuelCard;
use App\Models\Transaction;
use App\Models\Station;
use Illuminate\Support\Facades\Hash;

echo "=== Création de données de test pour Dashboard ===\n\n";

// 1. Créer/récupérer l'utilisateur
$user = User::where('email', 'test@example.com')->first();
if (!$user) {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
    ]);
    echo "✓ Utilisateur créé\n";
} else {
    echo "✓ Utilisateur existant trouvé\n";
}

// 2. Créer des stations
$stations = [
    ['name' => 'Total Tunis', 'latitude' => 36.8065, 'longitude' => 10.1815, 'services' => ['carwash', 'shop']],
    ['name' => 'Shell Ariana', 'latitude' => 36.8625, 'longitude' => 10.1956, 'services' => ['shop']],
    ['name' => 'Agil Lac', 'latitude' => 36.8389, 'longitude' => 10.2378, 'services' => ['carwash']],
];

foreach ($stations as $stationData) {
    Station::firstOrCreate(
        ['name' => $stationData['name']],
        $stationData
    );
}
echo "✓ " . count($stations) . " stations créées\n";

// 3. Créer des véhicules
$vehicles = [
    ['plate_number' => 'TUN-1234', 'model' => 'Peugeot 208', 'fuel_type' => 'Essence', 'average_consumption' => 5.5],
    ['plate_number' => 'TUN-5678', 'model' => 'Renault Clio', 'fuel_type' => 'Diesel', 'average_consumption' => 4.8],
];

$createdVehicles = [];
foreach ($vehicles as $vehicleData) {
    $vehicle = Vehicle::firstOrCreate(
        ['plate_number' => $vehicleData['plate_number']],
        array_merge($vehicleData, ['user_id' => $user->id])
    );
    $createdVehicles[] = $vehicle;
}
echo "✓ " . count($createdVehicles) . " véhicules créés\n";

// 4. Créer des cartes carburant
$fuelCards = [];
foreach ($createdVehicles as $vehicle) {
    $card = FuelCard::firstOrCreate(
        ['card_number' => 'CARD-' . $vehicle->plate_number],
        [
            'user_id' => $user->id,
            'balance' => 500.00,
            'authorized_products' => ['essence', 'diesel'],
            'status' => 'active'
        ]
    );
    $fuelCards[] = $card;
}
echo "✓ " . count($fuelCards) . " cartes carburant créées\n";

// 5. Créer des transactions (derniers 30 jours)
$transactionCount = 0;
for ($i = 0; $i < 15; $i++) {
    $date = now()->subDays(rand(0, 30));
    $vehicle = $createdVehicles[array_rand($createdVehicles)];
    $station = $stations[array_rand($stations)];
    $liters = rand(20, 60);
    $pricePerLiter = rand(180, 220) / 100; // 1.80 à 2.20 TND
    
    Transaction::create([
        'user_id' => $user->id,
        'fuel_card_id' => $fuelCards[0]->id,
        'vehicle_id' => $vehicle->id,
        'date' => $date,
        'quantity_liters' => $liters,
        'price_per_liter' => $pricePerLiter,
        'amount' => $liters * $pricePerLiter,
        'station_name' => $station['name'],
        'average_consumption' => $vehicle->average_consumption,
    ]);
    $transactionCount++;
}
echo "✓ {$transactionCount} transactions créées\n\n";

echo "=== Données de test créées avec succès! ===\n\n";
echo "Vous pouvez maintenant tester avec:\n";
echo "1. Login: POST http://localhost:8000/api/login\n";
echo "   Body: {\"email\":\"test@example.com\",\"password\":\"password123\"}\n\n";
echo "2. Dashboard: GET http://localhost:8000/api/dashboard\n";
echo "   Header: Authorization: Bearer YOUR_TOKEN\n";
