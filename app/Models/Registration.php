<?php

namespace App\Models;

use App\Enums\RegistrationStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pendaftaran tim yang menunggu konfirmasi pembayaran oleh Superadmin.
 *
 * @property string $id
 * @property string $code
 * @property string $team_name
 * @property string $owner_name
 * @property string $owner_email
 * @property string $owner_whatsapp
 * @property string $owner_password
 * @property int $plan_id
 * @property int $amount
 * @property string $source
 * @property RegistrationStatus $status
 * @property string|null $team_id
 * @property \Illuminate\Support\Carbon|null $processed_at
 * @property-read Plan $plan
 * @property-read Team|null $team
 */
class Registration extends Model
{
    use HasUuids;

    protected $fillable = [
        'code', 'team_name', 'owner_name', 'owner_email', 'owner_whatsapp', 'owner_password',
        'plan_id', 'amount', 'source', 'status', 'team_id', 'processed_by', 'processed_at', 'note', 'ip_address',
    ];

    protected $hidden = ['owner_password'];

    protected function casts(): array
    {
        return [
            'status' => RegistrationStatus::class,
            'amount' => 'integer',
            'processed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Plan, $this> */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /** @return BelongsTo<Team, $this> */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /** @return BelongsTo<User, $this> */
    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function amountLabel(): string
    {
        return 'Rp'.number_format($this->amount, 0, ',', '.');
    }
}
