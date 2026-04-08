<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Insight extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['user_id', 'type', 'description', 'generated_at'];

    protected $casts = ['generated_at' => 'datetime'];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn($model) => $model->id ??= (string) Str::uuid());
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function generateRecommendation(): string
    {
        // Logique IA / règles métier ici
        return "Réduisez la vitesse de 10 km/h pour économiser ~8% de carburant.";
    }
}