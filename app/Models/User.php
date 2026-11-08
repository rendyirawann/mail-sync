<?php

namespace App\Models;

use App\Enums\TeamRole;
use Cog\Contracts\Ban\Bannable as BannableContract;
use Cog\Laravel\Ban\Traits\Bannable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage; // 1. Ini penawar errornya
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements BannableContract
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    // 2. Masukkan HasUuids ke dalam use
    use Bannable, HasFactory, HasRoles, HasUuids, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'no_wa',
        'avatar',
        'last_ip',
        'last_login',
        'banned_at',
        'nik',
        'phone',
        'is_active',
        'password',
        'social_id',
        'social_type',
        'team_id',
        'team_role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'team_role' => TeamRole::class,
        ];
    }

    /** @return BelongsTo<Team, $this> */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /** Akun platform (bukan anggota tim) yang memegang peran Superadmin. */
    public function isSuperadmin(): bool
    {
        return $this->team_id === null && $this->hasRole(['Superadmin', 'superadmin']);
    }

    public function isTeamMember(): bool
    {
        return $this->team_id !== null;
    }

    public function isOwner(): bool
    {
        return $this->team_role === TeamRole::Owner;
    }

    /**
     * Cek peran tim, mis. $user->hasTeamRole(TeamRole::Owner, TeamRole::Admin).
     */
    public function hasTeamRole(TeamRole ...$roles): bool
    {
        return $this->team_role !== null && in_array($this->team_role, $roles, true);
    }

    /**
     * Public URL of the profile picture, or null when there is none.
     *
     * Uploads live on the "public" disk under user/avatar/. Older records may
     * still reference a bundled theme avatar; both are resolved here so views
     * never have to guess, and a missing file yields null so the initials
     * fallback is used instead of a broken image.
     */
    public function getAvatarUrlAttribute(): ?string
    {
        $file = $this->avatar;

        if (! is_string($file) || trim($file) === '') {
            return null;
        }

        if (Str::startsWith($file, ['http://', 'https://'])) {
            return $file;
        }

        if (Storage::disk('public')->exists('user/avatar/'.$file)) {
            return Storage::disk('public')->url('user/avatar/'.$file);
        }

        if (is_file(public_path('assets/media/avatars/'.$file))) {
            return asset('assets/media/avatars/'.$file);
        }

        return null;
    }

    /**
     * Avatar URL that is always renderable.
     *
     * Use this for a plain <img>; the <x-avatar> component prefers
     * avatar_url so it can show initials instead of a placeholder image.
     */
    public function getAvatarDisplayUrlAttribute(): string
    {
        return $this->avatar_url ?? asset('assets/media/avatars/blank.png');
    }

    /** Up to two uppercase initials, used as the avatar fallback. */
    public function getInitialsAttribute(): string
    {
        $words = preg_split('/\s+/', trim((string) $this->name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($words === []) {
            return 'U';
        }

        $initials = mb_substr($words[0], 0, 1);

        if (isset($words[1])) {
            $initials .= mb_substr($words[1], 0, 1);
        }

        return Str::upper($initials);
    }

    /** Primary role name for display purposes. */
    public function getRoleLabelAttribute(): string
    {
        return $this->roles->first()->name ?? 'User';
    }
}
