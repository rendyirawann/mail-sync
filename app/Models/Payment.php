<?php

namespace App\Models;

use App\Enums\PaymentType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pembayaran yang sudah diverifikasi Superadmin, beserta bukti transfer (disk privat).
 *
 * @property string $id
 * @property string|null $team_id
 * @property PaymentType $type
 * @property int $amount
 * @property string|null $proof_path
 * @property string|null $proof_mime
 * @property \Illuminate\Support\Carbon|null $paid_at
 * @property \Illuminate\Support\Carbon|null $period_start
 * @property \Illuminate\Support\Carbon|null $period_end
 * @property-read Plan $plan
 * @property-read Team|null $team
 */
class Payment extends Model
{
    use HasUuids;

    protected $fillable = [
        'team_id', 'registration_id', 'plan_id', 'type', 'amount', 'proof_path', 'proof_mime',
        'paid_at', 'period_start', 'period_end', 'verified_by', 'note',
    ];

    protected function casts(): array
    {
        return [
            'type' => PaymentType::class,
            'amount' => 'integer',
            'paid_at' => 'datetime',
            'period_start' => 'datetime',
            'period_end' => 'datetime',
        ];
    }

    /** @return BelongsTo<Team, $this> */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /** @return BelongsTo<Plan, $this> */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /** @return BelongsTo<User, $this> */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function amountLabel(): string
    {
        return 'Rp'.number_format($this->amount, 0, ',', '.');
    }
}
