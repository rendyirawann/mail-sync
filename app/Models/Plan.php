<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Paket langganan (Bulanan / Lifetime). Harga diatur Superadmin.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property int $price
 * @property int|null $duration_months
 * @property int $max_members
 * @property bool $is_active
 */
class Plan extends Model
{
    protected $fillable = ['code', 'name', 'price', 'duration_months', 'max_members', 'description', 'is_active'];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'duration_months' => 'integer',
            'max_members' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function isLifetime(): bool
    {
        return $this->duration_months === null;
    }

    /** Harga dalam format Rupiah, mis. "Rp39.000". */
    public function priceLabel(): string
    {
        return 'Rp'.number_format($this->price, 0, ',', '.');
    }

    public function periodLabel(): string
    {
        if ($this->isLifetime()) {
            return 'sekali bayar';
        }

        return $this->duration_months === 1 ? 'per bulan' : "per {$this->duration_months} bulan";
    }

    /** @param Builder<Plan> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('price');
    }
}
