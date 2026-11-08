<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTeam;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Bidang / divisi dalam tim (tujuan disposisi via pegawai).
 *
 * @property int $id
 * @property string $name
 * @property string|null $code
 */
class Division extends Model
{
    use BelongsToTeam;

    protected $fillable = ['team_id', 'name', 'code', 'sort_order'];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /** @return HasMany<Employee, $this> */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
