<?php

namespace App\Models;

use App\Enums\TeamRole;
use App\Enums\TeamStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Tim pelanggan (tenant). Diakses lewat /{slug}.
 *
 * @property string $id
 * @property string $slug
 * @property string $name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $address
 * @property int $plan_id
 * @property TeamStatus $status
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon|null $frozen_at
 * @property array<string, bool>|null $modules
 * @property array<string, mixed>|null $profile
 * @property-read Plan $plan
 */
class Team extends Model
{
    use HasUuids, SoftDeletes;

    /** Modul opsional yang bisa dibuka/dikunci owner. */
    public const OPTIONAL_MODULES = [
        'lembur' => 'Monitoring Lembur',
        'perjadin' => 'Monitoring Perjalanan Dinas',
        'honorarium' => 'Monitoring Honorarium',
    ];

    protected $fillable = [
        'slug', 'name', 'email', 'phone', 'address', 'plan_id', 'status',
        'started_at', 'expires_at', 'frozen_at', 'modules', 'profile',
    ];

    protected function casts(): array
    {
        return [
            'status' => TeamStatus::class,
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
            'frozen_at' => 'datetime',
            'modules' => 'array',
            'profile' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return BelongsTo<Plan, $this> */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** @return HasOne<User, $this> */
    public function owner(): HasOne
    {
        return $this->hasOne(User::class)->where('team_role', TeamRole::Owner->value);
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('paid_at');
    }

    public function isLifetime(): bool
    {
        return $this->expires_at === null;
    }

    /** Boleh menambah/mengubah data (bukan beku/nonaktif). */
    public function isWritable(): bool
    {
        return $this->status === TeamStatus::Active;
    }

    /** Sisa hari langganan; null untuk lifetime, negatif bila lewat. */
    public function daysLeft(): ?int
    {
        if ($this->expires_at === null) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->expires_at->copy()->startOfDay(), false);
    }

    public function seatsUsed(): int
    {
        return $this->users()->count();
    }

    public function hasFreeSeat(): bool
    {
        return $this->seatsUsed() < $this->plan->max_members;
    }

    public function moduleEnabled(string $key): bool
    {
        return (bool) ($this->modules[$key] ?? false);
    }

    public function profileValue(string $key, mixed $default = null): mixed
    {
        return data_get($this->profile, $key, $default);
    }

    /** URL login tim, mis. https://domain/k7qp/login. */
    public function loginUrl(): string
    {
        return route('team.login', $this);
    }
}
