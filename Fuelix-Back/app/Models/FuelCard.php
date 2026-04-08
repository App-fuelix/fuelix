<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class FuelCard extends Model
{
    use HasFactory, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'vehicle_id',
        'card_number',
        'issuer',
        'expiry_month',
        'expiry_year',
        'balance',
        'authorized_products',
        'status',
    ];

    protected $casts = [
        'authorized_products' => 'array',
        'balance'             => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn($model) => $model->id ??= (string) Str::uuid());
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Optionnel si une carte est liée à un véhicule spécifique
    public function vehicle(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function transactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    // Méthodes métier
    public function recharge(float $amount): bool
    {
        if ($amount <= 0) return false;
        $this->balance += $amount;
        return $this->save();
    }

    public function deduct(float $amount): bool
    {
        if ($amount <= 0 || $this->balance < $amount) {
            return false;
        }
        $this->balance -= $amount;
        return $this->save();
    }

    // Pour l'affichage dans l'API
    public function getMaskedNumberAttribute(): string
    {
        if (strlen($this->card_number) < 4) return '****';
        return '**** ' . substr($this->card_number, -4);
    }

    public function getValidThruAttribute(): string
    {
        $month = $this->expiry_month ?? '12';
        $year  = substr($this->expiry_year ?? '27', -2);
        return "$month/$year";
    }

    public function isExpired(): bool
    {
        if (!$this->expiry_month || !$this->expiry_year) {
            return false;
        }
        
        $year = strlen($this->expiry_year) === 2 
            ? '20' . $this->expiry_year 
            : $this->expiry_year;
        
        $expiryDate = \Carbon\Carbon::createFromDate($year, $this->expiry_month, 1)->endOfMonth();
        
        return now()->isAfter($expiryDate);
    }

    public function checkAndUpdateStatus(): void
    {
        if ($this->isExpired() && $this->status !== 'expired') {
            $this->update(['status' => 'expired']);
        }
    }
}