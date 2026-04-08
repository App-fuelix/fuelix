<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Station extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name', 'latitude', 'longitude', 'services'
    ];

    protected $casts = [
        'services' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn($model) => $model->id ??= (string) Str::uuid());
    }

    public function transactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Transaction::class, 'station_name', 'name');
    }

    public function getNearbyStations(float $lat, float $lon, float $radiusKm = 10): \Illuminate\Database\Eloquent\Collection
    {
        // Exemple simplifié (à améliorer avec PostGIS ou formule haversine)
        return self::query()
            ->whereRaw("
                (6371 * acos(cos(radians(?)) * cos(radians(latitude)) *
                cos(radians(longitude) - radians(?)) +
                sin(radians(?)) * sin(radians(latitude)))) < ?",
                [$lat, $lon, $lat, $radiusKm]
            )
            ->get();
    }
}