<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\FirestoreService;

class StationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userLat = $request->query('lat');
        $userLng = $request->query('lng');
        $radiusKm = $request->filled('radius_km') ? (float) $request->query('radius_km') : null;
        $governorate = strtolower((string) $request->query('governorate', ''));

        // Read stations from Firestore collection 'station'. If Firestore is
        // unavailable or empty, return an empty list and let the client handle fallback.
        $stations = [];
        try {
            $fs = app(FirestoreService::class);

            if ($governorate !== '') {
                $docs = $fs->query('station', [["governorate", 'EQUAL', $governorate]], null);
            } else {
                $docs = $fs->list('station');
            }

            foreach ($docs as $d) {
                $services = $d['services'] ?? [];
                if (is_string($services)) {
                    $decoded = json_decode($services, true);
                    $services = is_array($decoded) ? $decoded : [];
                }
                // Normalize services to lowercase strings for reliable filtering
                $services = array_map(fn($v) => strtolower((string) $v), (array) $services);

                $stations[] = [
                    'id' => $d['id'] ?? '',
                    'name' => $d['name'] ?? '',
                    'latitude' => isset($d['latitude']) ? (float) $d['latitude'] : 0.0,
                    'longitude' => isset($d['longitude']) ? (float) $d['longitude'] : 0.0,
                    'brand' => $d['brand'] ?? '',
                    'governorate' => $d['governorate'] ?? '',
                    'distance_km' => 0.0,
                    'services' => $services,
                    'is_open' => isset($d['is_open']) ? (bool) $d['is_open'] : true,
                ];
            }
        } catch (\Throwable $e) {
            $stations = [];
        }

        // Basic filtering by service
        if ($request->has('service')) {
            $service = strtolower((string) $request->query('service'));
            $stations = array_values(array_filter($stations, function($s) use ($service) {
                return in_array($service, array_map(fn($v) => strtolower((string) $v), (array) ($s['services'] ?? [])));
            }));
        }

        // Optional filtering by governorate/region
        if ($governorate !== '') {
            $stations = array_values(array_filter($stations, function ($station) use ($governorate) {
                return strtolower((string) ($station['governorate'] ?? '')) === $governorate;
            }));
        }

        if (is_numeric($userLat) && is_numeric($userLng)) {
            $userLat = (float) $userLat;
            $userLng = (float) $userLng;

            foreach ($stations as &$station) {
                $station['distance_km'] = $this->haversineKm(
                    $userLat,
                    $userLng,
                    (float) $station['latitude'],
                    (float) $station['longitude']
                );
            }
            unset($station);

            if ($radiusKm !== null) {
                $stations = array_values(array_filter($stations, function ($station) use ($radiusKm) {
                    return ($station['distance_km'] ?? INF) <= $radiusKm;
                }));
            }

            usort($stations, fn($a, $b) => $a['distance_km'] <=> $b['distance_km']);
        } else {
            usort($stations, fn($a, $b) => $a['distance_km'] <=> $b['distance_km']);
        }

        return response()->json([
            'status' => 200,
            'body' => [
                'stations' => $stations
            ]
        ]);
    }

    private function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadiusKm = 6371.0;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earthRadiusKm * (2 * atan2(sqrt($a), sqrt(1 - $a)));
    }
}
