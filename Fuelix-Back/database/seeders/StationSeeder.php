<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Services\FirestoreService;

class StationSeeder extends Seeder
{
    public function run(): void
    {
        if (method_exists($this, 'command') && $this->command) {
            $this->command->info('StationSeeder: starting');
        }
        $stations = [
            ['id' => 'st_001','name' => 'TotalEnergies Ariana','latitude' => 36.8665,'longitude' => 10.1647,'brand' => 'Total','governorate' => 'Ariana','services' => ['fuel','carwash','lubricants','shop'],'is_open' => true],
            ['id' => 'st_002','name' => 'Shell Ennasr','latitude' => 36.8588,'longitude' => 10.1542,'brand' => 'Shell','governorate' => 'Ariana','services' => ['fuel','lubricants','cafe'],'is_open' => true],
            ['id' => 'st_003','name' => 'Agil Menzah 6','latitude' => 36.8432,'longitude' => 10.1685,'brand' => 'Agil','governorate' => 'Tunis','services' => ['fuel','carwash'],'is_open' => true],
            ['id' => 'st_004','name' => 'Vivo Energy Lac 2','latitude' => 36.8375,'longitude' => 10.2789,'brand' => 'Shell','governorate' => 'Tunis','services' => ['fuel','lubricants','shop','carwash'],'is_open' => true],
            ['id' => 'st_005','name' => 'Oilibya Centre Urbain Nord','latitude' => 36.8465,'longitude' => 10.2012,'brand' => 'Oilibya','governorate' => 'Tunis','services' => ['fuel','shop'],'is_open' => true],
            ['id' => 'st_006','name' => 'Shell La Marsa','latitude' => 36.8782,'longitude' => 10.3241,'brand' => 'Shell','governorate' => 'Tunis','services' => ['fuel','carwash','shop'],'is_open' => true],
            ['id' => 'st_007','name' => 'Agil Sfax Centre','latitude' => 34.7406,'longitude' => 10.7603,'brand' => 'Agil','governorate' => 'Sfax','services' => ['fuel','shop','lubricants'],'is_open' => true],
            ['id' => 'st_008','name' => 'TotalEnergies Sousse','latitude' => 35.8256,'longitude' => 10.6406,'brand' => 'Total','governorate' => 'Sousse','services' => ['fuel','carwash','cafe'],'is_open' => true],
            ['id' => 'st_009','name' => 'Oilibya Monastir','latitude' => 35.7770,'longitude' => 10.8262,'brand' => 'Oilibya','governorate' => 'Monastir','services' => ['fuel','shop'],'is_open' => true],
            ['id' => 'st_010','name' => 'Shell Nabeul','latitude' => 36.4538,'longitude' => 10.7376,'brand' => 'Shell','governorate' => 'Nabeul','services' => ['fuel','lubricants'],'is_open' => true],
            ['id' => 'st_011','name' => 'Agil Tunis Centre','latitude' => 36.8065,'longitude' => 10.1815,'brand' => 'Agil','governorate' => 'Tunis','services' => ['fuel','carwash','shop'],'is_open' => true],
            ['id' => 'st_012','name' => 'TotalEnergies Bizerte','latitude' => 37.2746,'longitude' => 9.8739,'brand' => 'Total','governorate' => 'Bizerte','services' => ['fuel','shop','lubricants'],'is_open' => true],
            ['id' => 'st_013','name' => 'Shell Gabes','latitude' => 33.8815,'longitude' => 10.0982,'brand' => 'Shell','governorate' => 'Gabes','services' => ['fuel','cafe'],'is_open' => true],
            ['id' => 'st_014','name' => 'Oilibya Medenine','latitude' => 33.3549,'longitude' => 10.5055,'brand' => 'Oilibya','governorate' => 'Medenine','services' => ['fuel','shop','carwash'],'is_open' => true],
            ['id' => 'st_015','name' => 'Agil Kairouan','latitude' => 35.6781,'longitude' => 10.0963,'brand' => 'Agil','governorate' => 'Kairouan','services' => ['fuel','lubricants','shop'],'is_open' => true],
            ['id' => 'st_016','name' => 'TotalEnergies Ben Arous','latitude' => 36.7531,'longitude' => 10.2189,'brand' => 'Total','governorate' => 'Ben Arous','services' => ['fuel','shop','carwash'],'is_open' => true],
            ['id' => 'st_017','name' => 'Oilibya Manouba','latitude' => 36.8082,'longitude' => 10.0978,'brand' => 'Oilibya','governorate' => 'Manouba','services' => ['fuel','lubricants'],'is_open' => true],
            ['id' => 'st_018','name' => 'Shell Zaghouan','latitude' => 36.4029,'longitude' => 10.1429,'brand' => 'Shell','governorate' => 'Zaghouan','services' => ['fuel','shop'],'is_open' => true],
            ['id' => 'st_019','name' => 'Agil Beja','latitude' => 36.7256,'longitude' => 9.1817,'brand' => 'Agil','governorate' => 'Beja','services' => ['fuel','shop','lubricants'],'is_open' => true],
            ['id' => 'st_020','name' => 'TotalEnergies Jendouba','latitude' => 36.5011,'longitude' => 8.7802,'brand' => 'Total','governorate' => 'Jendouba','services' => ['fuel','cafe','shop'],'is_open' => true],
            ['id' => 'st_021','name' => 'Oilibya Siliana','latitude' => 36.0849,'longitude' => 9.3715,'brand' => 'Oilibya','governorate' => 'Siliana','services' => ['fuel','shop'],'is_open' => true],
            ['id' => 'st_022','name' => 'Shell Kasserine','latitude' => 35.1676,'longitude' => 8.8365,'brand' => 'Shell','governorate' => 'Kasserine','services' => ['fuel','lubricants','shop'],'is_open' => true],
            ['id' => 'st_023','name' => 'Agil Gafsa','latitude' => 34.4250,'longitude' => 8.7842,'brand' => 'Agil','governorate' => 'Gafsa','services' => ['fuel','carwash'],'is_open' => true],
            ['id' => 'st_024','name' => 'TotalEnergies Tozeur','latitude' => 33.9197,'longitude' => 8.1335,'brand' => 'Total','governorate' => 'Tozeur','services' => ['fuel','shop','cafe'],'is_open' => true],
            ['id' => 'st_025','name' => 'Oilibya Kebili','latitude' => 33.7044,'longitude' => 8.9690,'brand' => 'Oilibya','governorate' => 'Kebili','services' => ['fuel','shop'],'is_open' => true],
            ['id' => 'st_026','name' => 'Shell Mahdia','latitude' => 35.5047,'longitude' => 11.0622,'brand' => 'Shell','governorate' => 'Mahdia','services' => ['fuel','carwash','shop'],'is_open' => true],
            ['id' => 'st_027','name' => 'Agil Tataouine','latitude' => 32.9297,'longitude' => 10.4518,'brand' => 'Agil','governorate' => 'Tataouine','services' => ['fuel','shop'],'is_open' => true],
            ['id' => 'st_028','name' => 'TotalEnergies Kef','latitude' => 36.1742,'longitude' => 8.7049,'brand' => 'Total','governorate' => 'Kef','services' => ['fuel','lubricants','shop'],'is_open' => true],
            ['id' => 'st_029','name' => 'Oilibya Sidi Bouzid','latitude' => 35.0382,'longitude' => 9.4849,'brand' => 'Oilibya','governorate' => 'Sidi Bouzid','services' => ['fuel','shop'],'is_open' => true],
            ['id' => 'st_030','name' => 'Shell Kairouan Sud','latitude' => 35.6645,'longitude' => 10.1027,'brand' => 'Shell','governorate' => 'Kairouan','services' => ['fuel','carwash','cafe'],'is_open' => true],
        ];

        try {
            $fs = app(FirestoreService::class);

            // Remove all existing station documents to ensure exact sync
            $existing = $fs->list('station');
            $deleted = 0;
            foreach ($existing as $doc) {
                if (!empty($doc['id'])) {
                    try { $fs->delete('station', $doc['id']); $deleted++; } catch (\Throwable $e) { /* ignore */ }
                }
            }

            if (method_exists($this, 'command') && $this->command) {
                $this->command->info("StationSeeder: deleted {$deleted} existing documents");
            }

            // Create fresh documents
            $created = 0;
            foreach ($stations as $s) {
                $payload = $s;
                if (isset($payload['id'])) {
                    $payload['_legacy_id'] = $payload['id'];
                    unset($payload['id']);
                }
                $fs->create('station', $payload);
                $created++;
            }

            if (method_exists($this, 'command') && $this->command) {
                $this->command->info("StationSeeder: created {$created} station documents");
            }
        } catch (\Throwable $e) {
            if (method_exists($this, 'command') && $this->command) {
                $this->command->error('StationSeeder failed: ' . $e->getMessage());
            } else {
                echo 'StationSeeder failed: ' . $e->getMessage() . PHP_EOL;
            }
            throw $e;
        }
    }
}
