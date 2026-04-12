<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class FirestoreUserService
{
    private const USERS_COLLECTION = 'users';
    private const FIRESTORE_SCOPE = 'https://www.googleapis.com/auth/datastore';

    private ?string $accessToken = null;
    private ?CarbonImmutable $accessTokenExpiresAt = null;

    private function projectId(): string
    {
        $projectId = (string) config('services.firebase.project_id', '');

        if ($projectId === '') {
            throw new RuntimeException('Missing FIREBASE_PROJECT_ID configuration.');
        }

        return $projectId;
    }

    private function usersCollectionName(): string
    {
        return (string) config('services.firebase.users_collection', self::USERS_COLLECTION);
    }

    private function credentialsPath(): string
    {
        $path = (string) config('services.firebase.credentials', '');

        if ($path === '') {
            throw new RuntimeException('Missing FIREBASE_CREDENTIALS configuration.');
        }

        if (! is_file($path)) {
            throw new RuntimeException("Firebase credentials file not found at: {$path}");
        }

        return $path;
    }

    private function baseDocumentUrl(): string
    {
        return sprintf(
            'https://firestore.googleapis.com/v1/projects/%s/databases/(default)/documents',
            $this->projectId()
        );
    }

    private function getAccessToken(): string
    {
        $now = CarbonImmutable::now();

        if ($this->accessToken && $this->accessTokenExpiresAt && $now->lt($this->accessTokenExpiresAt->subSeconds(30))) {
            return $this->accessToken;
        }

        $credentialsJson = json_decode((string) file_get_contents($this->credentialsPath()), true);

        if (! is_array($credentialsJson)) {
            throw new RuntimeException('Invalid Firebase credentials JSON.');
        }

        $credentials = new ServiceAccountCredentials(self::FIRESTORE_SCOPE, $credentialsJson);
        $tokenData = $credentials->fetchAuthToken();

        $accessToken = (string) Arr::get($tokenData, 'access_token', '');

        if ($accessToken === '') {
            throw new RuntimeException('Failed to fetch Google OAuth access token for Firestore.');
        }

        $expiresIn = (int) Arr::get($tokenData, 'expires_in', 3500);

        $this->accessToken = $accessToken;
        $this->accessTokenExpiresAt = $now->addSeconds(max(60, $expiresIn));

        return $this->accessToken;
    }

    private function request(string $method, string $url, array $payload = []): array
    {
        $response = Http::withToken($this->getAccessToken())
            ->acceptJson()
            ->send($method, $url, $payload === [] ? [] : ['json' => $payload]);

        if (! $response->successful()) {
            throw new RuntimeException(
                sprintf('Firestore REST request failed (%d): %s', $response->status(), (string) $response->body())
            );
        } 

        $json = $response->json();

        return is_array($json) ? $json : [];
    }

    private function encodeFields(array $data): array
    {
        $fields = [];

        foreach ($data as $key => $value) {
            if ($value === null) {
                $fields[$key] = ['nullValue' => null];
                continue;
            }

            if (is_bool($value)) {
                $fields[$key] = ['booleanValue' => $value];
                continue;
            }

            if (is_int($value)) {
                $fields[$key] = ['integerValue' => (string) $value];
                continue;
            }

            if (is_float($value)) {
                $fields[$key] = ['doubleValue' => $value];
                continue;
            }

            $fields[$key] = ['stringValue' => (string) $value];
        }

        return $fields;
    }

    private function decodeFields(array $fields): array
    {
        $decoded = [];

        foreach ($fields as $key => $typedValue) {
            if (! is_array($typedValue)) {
                continue;
            }

            if (array_key_exists('stringValue', $typedValue)) {
                $decoded[$key] = $typedValue['stringValue'];
                continue;
            }

            if (array_key_exists('integerValue', $typedValue)) {
                $decoded[$key] = (int) $typedValue['integerValue'];
                continue;
            }

            if (array_key_exists('doubleValue', $typedValue)) {
                $decoded[$key] = (float) $typedValue['doubleValue'];
                continue;
            }

            if (array_key_exists('booleanValue', $typedValue)) {
                $decoded[$key] = (bool) $typedValue['booleanValue'];
                continue;
            }

            if (array_key_exists('timestampValue', $typedValue)) {
                $decoded[$key] = $typedValue['timestampValue'];
                continue;
            }

            if (array_key_exists('nullValue', $typedValue)) {
                $decoded[$key] = null;
            }
        }

        return $decoded;
    }

    private function decodeDocument(array $document): array
    {
        $name = (string) Arr::get($document, 'name', '');
        $segments = explode('/', $name);
        $id = end($segments) ?: '';

        return [
            'id' => $id,
            ...$this->decodeFields((array) Arr::get($document, 'fields', [])),
        ];
    }

    private function documentUrl(string $id): string
    {
        return sprintf('%s/%s/%s', $this->baseDocumentUrl(), $this->usersCollectionName(), $id);
    }

    private function runQueryUrl(): string
    {
        return sprintf('%s:runQuery', $this->baseDocumentUrl());
    }

    private function usersCollectionUrl(): string
    {
        return sprintf('%s/%s', $this->baseDocumentUrl(), $this->usersCollectionName());
    }

    public function findByEmail(string $email): ?array
    {
        $query = [
            'structuredQuery' => [
                'from' => [
                    ['collectionId' => $this->usersCollectionName()],
                ],
                'where' => [
                    'fieldFilter' => [
                        'field' => ['fieldPath' => 'email'],
                        'op' => 'EQUAL',
                        'value' => ['stringValue' => strtolower($email)],
                    ],
                ],
                'limit' => 1,
            ],
        ];

        $results = $this->request('POST', $this->runQueryUrl(), $query);

        foreach ($results as $result) {
            $document = Arr::get($result, 'document');

            if (is_array($document)) {
                return $this->decodeDocument($document);
            }
        }

        return null;
    }

    public function findById(string $id): ?array
    {
        $response = Http::withToken($this->getAccessToken())
            ->acceptJson()
            ->get($this->documentUrl($id));

        if ($response->status() === 404) {
            return null;
        }

        if (! $response->successful()) {
            throw new RuntimeException(
                sprintf('Firestore REST request failed (%d): %s', $response->status(), (string) $response->body())
            );
        }

        $json = $response->json();

        return is_array($json) ? $this->decodeDocument($json) : null;
    }

    public function createUserFromFirebase(array $data): array
    {
        $now = CarbonImmutable::now()->toIso8601String();

        $data['created_at'] = $now;
        $data['updated_at'] = $now;

        $document = $this->request('POST', $this->usersCollectionUrl(), [
            'fields' => $this->encodeFields($data),
        ]);

        return $this->decodeDocument($document);
    }

    public function createUser(string $name, string $email, string $plainPassword, ?string $phone = null, ?string $city = null): array
    {
        $now = CarbonImmutable::now()->toIso8601String();

        $data = [
            'name'       => $name,
            'email'      => strtolower($email),
            'password'   => Hash::make($plainPassword),
            'created_at' => $now,
            'updated_at' => $now,
        ];

        if ($phone !== null) $data['phone'] = $phone;
        if ($city  !== null) $data['city']  = $city;

        $document = $this->request('POST', $this->usersCollectionUrl(), [
            'fields' => $this->encodeFields($data),
        ]);

        return $this->decodeDocument($document);
    }

    public function updateUser(string $id, array $data): ?array
    {
        $existing = $this->findById($id);

        if (! $existing) {
            return null;
        }

        $updated = [
            ...$existing,
            ...$data,
            'updated_at' => CarbonImmutable::now()->toIso8601String(),
        ];

        unset($updated['id']);

        $this->request('PATCH', $this->documentUrl($id), [
            'fields' => $this->encodeFields($updated),
        ]);

        return $this->findById($id);
    }

    public function verifyCredentials(string $email, string $plainPassword): ?array
    {
        $user = $this->findByEmail($email);

        if (! $user) {
            return null;
        }

        if (! Hash::check($plainPassword, (string) ($user['password'] ?? ''))) {
            return null;
        }

        return $user;
    }
}
