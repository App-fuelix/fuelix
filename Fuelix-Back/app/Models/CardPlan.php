<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * CardPlan — Represents a fuel card tier/type defined by the admin.
 *
 * Stored in Firestore collection: card_plans
 *
 * Predefined tiers:
 *   - basic    → fuel only          → color #1E90FF (blue)
 *   - standard → fuel + carwash     → color #C0C0C0 (silver)
 *   - premium  → fuel+carwash+lubricants → color #FFD700 (gold)
 */
class CardPlan extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'description',
        'color',
        'authorized_products',
        'tier_level',      // 1 = basic, 2 = standard, 3 = premium
        'is_active',
    ];

    protected $casts = [
        'authorized_products' => 'array',
        'is_active'           => 'boolean',
        'tier_level'          => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn($model) => $model->id ??= (string) Str::uuid());
    }

    /**
     * All fuel cards using this plan.
     */
    public function fuelCards(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(FuelCard::class);
    }

    /**
     * Returns the 3 default plans as arrays (used for seeding Firestore).
     */
    public static function defaultPlans(): array
    {
        return [
            [
                'id'                  => 'plan_basic',
                'name'                => 'Basic',
                'description'         => 'Accès carburant uniquement',
                'color'               => '#1E90FF',
                'authorized_products' => ['fuel'],
                'tier_level'          => 1,
                'is_active'           => true,
            ],
            [
                'id'                  => 'plan_standard',
                'name'                => 'Standard',
                'description'         => 'Carburant + Lavage voiture',
                'color'               => '#C0C0C0',
                'authorized_products' => ['fuel', 'carwash'],
                'tier_level'          => 2,
                'is_active'           => true,
            ],
            [
                'id'                  => 'plan_premium',
                'name'                => 'Premium',
                'description'         => 'Tous les services : carburant, lavage et lubrifiants',
                'color'               => '#FFD700',
                'authorized_products' => ['fuel', 'carwash', 'lubricants'],
                'tier_level'          => 3,
                'is_active'           => true,
            ],
        ];
    }
}
