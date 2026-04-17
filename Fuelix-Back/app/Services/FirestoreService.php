<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FirestoreService
{
    private const SCOPE = 'https://www.googleapis.com/auth/datastore';

    private ?string $accessToken = null;
    private ?CarbonImmutable $tokenExpiresAt = null;

    private function projectId(): string
    {
        $id = (string) config('services.firebase.project_id', '');
        if ($id === '') throw new RuntimeException('Missing FIREBASE_PROJECT_ID.');
        return $id;
    }

    private function credentialsPath(): string
    {
        $path = (string) config('services.firebase.credentials', '');
        if ($path === '' || !is_file($path)) {
            throw new RuntimeException("Firebase credentials not found: {$path}");
        }
        return $path;
    }

    private function baseUrl(): string
    {
        return "https://firestore.googleapis.com/v1/projects/{$this->projectId()}/databases/(default)/documents";
    }

    private function getToken(): string
    {
        $now = CarbonImmutable::now();
        if ($this->accessToken && $this->tokenExpiresAt && $now->lt($this->tokenExpiresAt->subSeconds(30))) {
            return $this->accessToken;
        }

        $json = json_decode(file_get_contents($this->credentialsPath()), true);
        $creds = new ServiceAccountCredentials(self::SCOPE, $json);
        $data = $creds->fetchAuthToken();
        $token = (string) Arr::get($data, 'access_token', '');

        if ($token === '') throw new RuntimeException('Failed to fetch Firebase access token.');

        $this->accessToken = $token;
        $this->tokenExpiresAt = $now->addSeconds((int) Arr::get($data, 'expires_in', 3500));

        return $this->accessToken;
    }

    private function request(string $method, string $url, array $payload = []): array
    {
        $req = Http::withToken($this->getToken())->acceptJson();
        $response = $payload ? $req->send($method, $url, ['json' => $payload]) : $req->send($method, $url);

        if (!$response->successful()) {
            throw new RuntimeException("Firestore {$method} failed ({$response->status()}): {$response->body()}");
        }

        return is_array($response->json()) ? $response->json() : [];
    }

    // -------------------------------------------------------------------------
    // Field encoding / decoding
    // -------------------------------------------------------------------------

    public function encode(array $data): array
    {
        $fields = [];
        foreach ($data as $key => $value) {
            if ($value === null)          { $fields[$key] = ['nullValue' => null]; }
            elseif (is_bool($value))      { $fields[$key] = ['booleanValue' => $value]; }
            elseif (is_int($value))       { $fields[$key] = ['integerValue' => (string) $value]; }
            elseif (is_float($value))     { $fields[$key] = ['doubleValue' => $value]; }
            elseif (is_array($value))     { $fields[$key] = ['stringValue' => json_encode($value)]; }
            else                          { $fields[$key] = ['stringValue' => (string) $value]; }
        }
        return $fields;
    }

    public function decode(array $fields): array
    {
        $out = [];
        foreach ($fields as $key => $typed) {
            if (!is_array($typed)) continue;
            if (array_key_exists('stringValue', $typed))    $out[$key] = $typed['stringValue'];
            elseif (array_key_exists('integerValue', $typed)) $out[$key] = (int) $typed['integerValue'];
            elseif (array_key_exists('doubleValue', $typed))  $out[$key] = (float) $typed['doubleValue'];
            elseif (array_key_exists('booleanValue', $typed)) $out[$key] = (bool) $typed['booleanValue'];
            elseif (array_key_exists('timestampValue', $typed)) $out[$key] = $typed['timestampValue'];
            elseif (array_key_exists('nullValue', $typed))    $out[$key] = null;
        }
        return $out;
    }

    private function decodeDoc(array $doc): array
    {
        $name = (string) Arr::get($doc, 'name', '');
        $parts = explode('/', $name);
        $id = end($parts) ?: '';
        return ['id' => $id, ...$this->decode((array) Arr::get($doc, 'fields', []))];
    }

    // -------------------------------------------------------------------------
    // CRUD — top-level collections
    // -------------------------------------------------------------------------

    public function collectionUrl(string $collection): string
    {
        return "{$this->baseUrl()}/{$collection}";
    }

    public function documentUrl(string $collection, string $id): string
    {
        return "{$this->baseUrl()}/{$collection}/{$id}";
    }

    /** Create a document (auto-ID) */
    public function create(string $collection, array $data): array
    {
        $now = CarbonImmutable::now()->toIso8601String();
        $data['created_at'] = $now;
        $data['updated_at'] = $now;

        $doc = $this->request('POST', $this->collectionUrl($collection), ['fields' => $this->encode($data)]);
        return $this->decodeDoc($doc);
    }

    /** Get a document by ID */
    public function get(string $collection, string $id): ?array
    {
        $response = Http::withToken($this->getToken())->acceptJson()->get($this->documentUrl($collection, $id));
        if ($response->status() === 404) return null;
        if (!$response->successful()) throw new RuntimeException("Firestore GET failed: {$response->body()}");
        $json = $response->json();
        return is_array($json) ? $this->decodeDoc($json) : null;
    }

    /** Update a document by ID */
    public function update(string $collection, string $id, array $data): ?array
    {
        $existing = $this->get($collection, $id);
        if (!$existing) return null;

        $merged = [...$existing, ...$data, 'updated_at' => CarbonImmutable::now()->toIso8601String()];
        unset($merged['id']);

        $this->request('PATCH', $this->documentUrl($collection, $id), ['fields' => $this->encode($merged)]);
        return $this->get($collection, $id);
    }

    /** Delete a document */
    public function delete(string $collection, string $id): void
    {
        Http::withToken($this->getToken())->delete($this->documentUrl($collection, $id));
    }

    /** List all documents in a collection */
    public function list(string $collection): array
    {
        $response = Http::withToken($this->getToken())->acceptJson()->get($this->collectionUrl($collection));
        if (!$response->successful()) return [];
        $json = $response->json();
        $documents = Arr::get($json, 'documents', []);
        return array_map(fn($doc) => $this->decodeDoc($doc), $documents);
    }

    /** Query with filters */
    public function query(string $collection, array $filters = [], ?int $limit = null): array
    {
        $where = [];

        if (count($filters) === 1) {
            [$field, $op, $value] = $filters[0];
            $where = [
                'fieldFilter' => [
                    'field' => ['fieldPath' => $field],
                    'op' => $op,
                    'value' => $this->encodeValue($value),
                ],
            ];
        } elseif (count($filters) > 1) {
            $conditions = array_map(fn($f) => [
                'fieldFilter' => [
                    'field' => ['fieldPath' => $f[0]],
                    'op' => $f[1],
                    'value' => $this->encodeValue($f[2]),
                ],
            ], $filters);
            $where = ['compositeFilter' => ['op' => 'AND', 'filters' => $conditions]];
        }

        $query = ['from' => [['collectionId' => $collection]]];
        if ($where) $query['where'] = $where;
        if ($limit) $query['limit'] = $limit;

        $results = $this->request('POST', "{$this->baseUrl()}:runQuery", ['structuredQuery' => $query]);

        $docs = [];
        foreach ($results as $result) {
            $doc = Arr::get($result, 'document');
            if (is_array($doc)) $docs[] = $this->decodeDoc($doc);
        }
        return $docs;
    }

    private function encodeValue(mixed $value): array
    {
        if ($value === null)      return ['nullValue' => null];
        if (is_bool($value))     return ['booleanValue' => $value];
        if (is_int($value))      return ['integerValue' => (string) $value];
        if (is_float($value))    return ['doubleValue' => $value];
        return ['stringValue' => (string) $value];
    }

    // -------------------------------------------------------------------------
    // Subcollections  (e.g. users/{uid}/fuel_cards)
    // -------------------------------------------------------------------------

    public function subCollectionUrl(string $parent, string $parentId, string $sub): string
    {
        return "{$this->baseUrl()}/{$parent}/{$parentId}/{$sub}";
    }

    public function subDocumentUrl(string $parent, string $parentId, string $sub, string $id): string
    {
        return "{$this->baseUrl()}/{$parent}/{$parentId}/{$sub}/{$id}";
    }

    public function subCreate(string $parent, string $parentId, string $sub, array $data): array
    {
        $now = CarbonImmutable::now()->toIso8601String();
        $data['created_at'] = $now;
        $data['updated_at'] = $now;

        $doc = $this->request('POST', $this->subCollectionUrl($parent, $parentId, $sub), ['fields' => $this->encode($data)]);
        return $this->decodeDoc($doc);
    }

    public function subGet(string $parent, string $parentId, string $sub, string $id): ?array
    {
        $response = Http::withToken($this->getToken())->acceptJson()->get($this->subDocumentUrl($parent, $parentId, $sub, $id));
        if ($response->status() === 404) return null;
        if (!$response->successful()) return null;
        $json = $response->json();
        return is_array($json) ? $this->decodeDoc($json) : null;
    }

    public function subUpdate(string $parent, string $parentId, string $sub, string $id, array $data): ?array
    {
        $existing = $this->subGet($parent, $parentId, $sub, $id);
        if (!$existing) return null;

        $merged = [...$existing, ...$data, 'updated_at' => CarbonImmutable::now()->toIso8601String()];
        unset($merged['id']);

        $this->request('PATCH', $this->subDocumentUrl($parent, $parentId, $sub, $id), ['fields' => $this->encode($merged)]);
        return $this->subGet($parent, $parentId, $sub, $id);
    }

    public function subDelete(string $parent, string $parentId, string $sub, string $id): void
    {
        Http::withToken($this->getToken())->delete($this->subDocumentUrl($parent, $parentId, $sub, $id));
    }

    public function subList(string $parent, string $parentId, string $sub): array
    {
        $response = Http::withToken($this->getToken())->acceptJson()->get($this->subCollectionUrl($parent, $parentId, $sub));
        if (!$response->successful()) return [];
        $json = $response->json();
        $documents = Arr::get($json, 'documents', []);
        return array_map(fn($doc) => $this->decodeDoc($doc), $documents);
    }

    public function subQuery(string $parent, string $parentId, string $sub, array $filters = []): array
    {
        $url = "{$this->baseUrl()}/{$parent}/{$parentId}:runQuery";

        $where = [];
        if (count($filters) === 1) {
            [$field, $op, $value] = $filters[0];
            $where = ['fieldFilter' => ['field' => ['fieldPath' => $field], 'op' => $op, 'value' => $this->encodeValue($value)]];
        } elseif (count($filters) > 1) {
            $conditions = array_map(fn($f) => ['fieldFilter' => ['field' => ['fieldPath' => $f[0]], 'op' => $f[1], 'value' => $this->encodeValue($f[2])]], $filters);
            $where = ['compositeFilter' => ['op' => 'AND', 'filters' => $conditions]];
        }

        $query = ['from' => [['collectionId' => $sub]]];
        if ($where) $query['where'] = $where;

        $results = $this->request('POST', $url, ['structuredQuery' => $query]);

        $docs = [];
        foreach ($results as $result) {
            $doc = Arr::get($result, 'document');
            if (is_array($doc)) $docs[] = $this->decodeDoc($doc);
        }
        return $docs;
    }
}
