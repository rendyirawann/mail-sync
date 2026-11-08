<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTeam;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Data pegawai (tidak memakan kuota akun). Dipakai sebagai penerima disposisi & penanda tangan.
 *
 * @property string $id
 * @property string $name
 * @property string|null $nip
 * @property string|null $position
 * @property string|null $user_id
 * @property bool $is_active
 * @property-read Division|null $division
 */
class Employee extends Model
{
    use BelongsToTeam, HasUuids;

    protected $fillable = ['team_id', 'division_id', 'user_id', 'name', 'nip', 'position', 'phone', 'email', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Division, $this> */
    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<Disposition, $this> */
    public function dispositions(): HasMany
    {
        return $this->hasMany(Disposition::class, 'to_employee_id');
    }

    /** @param Builder<Employee> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('name');
    }

    /** "Nama — Jabatan (Divisi)" untuk pilihan dropdown. */
    public function displayName(): string
    {
        $parts = array_filter([$this->position, $this->division?->name]);

        return $this->name.($parts ? ' — '.implode(', ', $parts) : '');
    }
}
