<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Rekening tujuan transfer yang ditampilkan di form pendaftaran.
 *
 * @property string $bank_name
 * @property string $account_number
 * @property string $account_holder
 */
class BankAccount extends Model
{
    protected $fillable = ['bank_name', 'account_number', 'account_holder', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    /** @param Builder<BankAccount> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }
}
