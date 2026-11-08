<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTeam;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modul opsional: monitoring perjalanan dinas.
 *
 * @property string $id
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon $end_date
 * @property int $cost
 * @property string $status
 * @property-read Employee $employee
 */
class BusinessTrip extends Model
{
    use BelongsToTeam, HasUuids;

    protected $fillable = ['team_id', 'employee_id', 'outgoing_letter_id', 'destination', 'purpose', 'start_date', 'end_date', 'cost', 'attachment_url', 'status'];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'cost' => 'integer',
        ];
    }

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /** @return BelongsTo<OutgoingLetter, $this> */
    public function assignmentLetter(): BelongsTo
    {
        return $this->belongsTo(OutgoingLetter::class, 'outgoing_letter_id');
    }
}
