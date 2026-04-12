<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class FirebaseTokenService
{
    private string $projectId;

    public function __construct()
    {
        $this->projectId = (string) config('services.firebase.project_id');
    }

    /**
     * Verify a Firebase ID token and return the decoded payload.
     */
    public function verifyIdToken(string $idToken): array
    {
        // Fetch Google's public keys
        $keysResponse = Http::get('https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com');

        if (!$keysResponse->successful()) {
            throw new RuntimeException('Failed to fetch Firebase public keys.');
        }

        $keys = $keysResponse->json();

        // Decode JWT header to get kid
        $parts = explode('.', $idToken);
        if (count($parts) !== 3) {
            throw new RuntimeException('Invalid Firebase ID token format.');
        }

        $header = json_decode(base64_decode(strtr($parts[0], '-_', '+/')), true);
        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

        if (!isset($header['kid']) || !isset($keys[$header['kid']])) {
            throw new RuntimeException('Firebase token key ID not found.');
        }

        // Verify signature
        $publicKey = openssl_pkey_get_public($keys[$header['kid']]);
        $dataToVerify = $parts[0] . '.' . $parts[1];
        $signature = base64_decode(strtr($parts[2], '-_', '+/'));

        $verified = openssl_verify($dataToVerify, $signature, $publicKey, OPENSSL_ALGO_SHA256);

        if ($verified !== 1) {
            throw new RuntimeException('Firebase token signature verification failed.');
        }

        // Verify claims
        $now = time();

        if (($payload['exp'] ?? 0) < $now) {
            throw new RuntimeException('Firebase token has expired.');
        }

        if (($payload['iat'] ?? 0) > $now + 300) {
            throw new RuntimeException('Firebase token issued in the future.');
        }

        if (($payload['aud'] ?? '') !== $this->projectId) {
            throw new RuntimeException('Firebase token audience mismatch.');
        }

        if (($payload['iss'] ?? '') !== 'https://securetoken.google.com/' . $this->projectId) {
            throw new RuntimeException('Firebase token issuer mismatch.');
        }

        return $payload;
    }
}
