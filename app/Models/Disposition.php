<?php

namespace App\Models;

use App\Enums\DispositionStatus;
use App\Models\Concerns\BelongsToTeam;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Disposisi surat masuk ke pegawai, bisa diteruskan berjenjang (parent_id).
 *
 * @property string $id
 * @property string $incoming_letter_id
 * @property string $to_employee_id
 * @property string $instruction
 * @property \Illuminate\Support\Carbon|null $due_date
 * @property DispositionStatus $status
 * @property string|null $response
 * @property-read IncomingLetter|null $letter
 * @property-read Employee|null $recipient
 */
class Disposition extends Model
{
    use BelongsToTeam, HasUuids;

    protected $fillable = ['team_id', 'incoming_letter_id', 'parent_id', 'from_user_id', 'to_employee_id', 'instruction', 'due_date', 'status', 'response', 'read_at', 'completed_at'];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'read_at' => 'datetime',
            'completed_at' => 'datetime',
            'status' => DispositionStatus::class,
        ];
    }

    /** @return BelongsTo<IncomingLetter, $this> */
    public function letter(): BelongsTo
    {
        return $this->belongsTo(IncomingLetter::class, 'incoming_letter_id');
    }

    /** @return BelongsTo<Disposition, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Disposition::class, 'parent_id');
    }

    /** @return BelongsTo<User, $this> */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    /** @return BelongsTo<Employee, $this> */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'to_employee_id');
    }

    /** @return HasMany<Disposition, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(Disposition::class, 'parent_id');
    }

    public function isOverdue(): bool
    {
        return $this->due_date !== null && $this->status !== DispositionStatus::Selesai && $this->due_date->isPast() && ! $this->due_date->isToday();
    }
}
