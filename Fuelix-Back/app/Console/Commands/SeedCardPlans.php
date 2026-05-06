<?php

namespace App\Console\Commands;

use App\Models\CardPlan;
use App\Services\FirestoreService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Seed the 3 default CardPlan tiers into Firestore.
 *
 * Usage:
 *   php artisan fuelix:seed-card-plans
 *   php artisan fuelix:seed-card-plans --force   (overwrite existing)
 */
class SeedCardPlans extends Command
{
    protected $signature   = 'fuelix:seed-card-plans {--force : Overwrite existing plans}';
    protected $description = 'Seed the 3 default card plans (Basic / Standard / Premium) into Firestore';

    public function __construct(private readonly FirestoreService $firestore)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('');
        $this->info('🔵 FueliX — Seeding Card Plans into Firestore');
        $this->info('──────────────────────────────────────────────');

        $plans  = CardPlan::defaultPlans();
        $force  = $this->option('force');
        $seeded = 0;
        $skipped = 0;

        foreach ($plans as $plan) {
            $this->line("  ► Checking plan: <comment>{$plan['name']}</comment> (id: {$plan['id']})");

            // Check if already exists
            $existing = $this->firestore->get('card_plans', $plan['id']);

            if ($existing && !$force) {
                $this->line("    <fg=yellow>⚠ Already exists — skipped (use --force to overwrite)</>"); 
                $skipped++;
                continue;
            }

            // Use PATCH directly on document URL with fixed ID (create or overwrite)
            $this->upsertPlan($plan);

            $colorDot = $this->colorDot($plan['name']);
            $products = implode(', ', $plan['authorized_products']);
            $this->line("    <fg=green>✓ {$colorDot} {$plan['name']} → [{$products}] — color: {$plan['color']}</>");
            $seeded++;
        }

        $this->info('');
        $this->info("✅ Done — {$seeded} plan(s) seeded, {$skipped} skipped.");
        $this->info('');

        if ($seeded > 0) {
            $this->table(
                ['ID', 'Name', 'Color', 'Tier', 'Products'],
                collect($plans)->map(fn($p) => [
                    $p['id'],
                    $p['name'],
                    $p['color'],
                    $p['tier_level'],
                    implode(', ', $p['authorized_products']),
                ])->toArray()
            );
        }

        $this->info('');
        $this->line('  Next step → assign a plan to a user card:');
        $this->line('  <fg=cyan>POST /api/admin/card-plans/assign</>');
        $this->line('  Body: { "user_id": "...", "card_id": "...", "plan_id": "plan_basic|plan_standard|plan_premium" }');
        $this->info('');

        return self::SUCCESS;
    }

    /**
     * PATCH to Firestore document with a fixed ID (creates or overwrites).
     * Firestore REST: PATCH on /documents/{collection}/{id} creates if not exists.
     */
    private function upsertPlan(array $plan): void
    {
        $data = [
            'name'                => $plan['name'],
            'description'         => $plan['description'],
            'color'               => $plan['color'],
            'authorized_products' => json_encode($plan['authorized_products']),
            'tier_level'          => $plan['tier_level'],
            'is_active'           => true,
        ];

        $projectId = (string) config('services.firebase.project_id');
        $baseUrl   = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents";
        $docUrl    = "{$baseUrl}/card_plans/{$plan['id']}";

        // Get token from FirestoreService via reflection (reuse existing auth)
        // We use the public encode() helper to format fields
        $fields = $this->firestore->encode($data);

        $credPath = (string) config('services.firebase.credentials');
        $json     = json_decode(file_get_contents($credPath), true);
        $creds    = new \Google\Auth\Credentials\ServiceAccountCredentials(
            'https://www.googleapis.com/auth/datastore',
            $json
        );
        $tokenData   = $creds->fetchAuthToken();
        $accessToken = $tokenData['access_token'];

        Http::withToken($accessToken)
            ->acceptJson()
            ->patch($docUrl, ['fields' => $fields]);
    }

    private function colorDot(string $plan): string
    {
        return match($plan) {
            'Basic'    => '🔵',
            'Standard' => '⚪',
            'Premium'  => '🟡',
            default    => '⚫',
        };
    }
}
