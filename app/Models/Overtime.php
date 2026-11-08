<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTeam;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modul opsional: monitoring lembur pegawai.
 *
 * @property string $id
 * @property \Illuminate\Support\Carbon $date
 * @property string $status
 * @property-read Employee $employee
 */
class Overtime extends Model
{
    use BelongsToTeam, HasUuids;

    protected $fillable = ['team_id', 'employee_id', 'date', 'start_time', 'end_time', 'hours', 'activity', 'attachment_url', 'status'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'hours' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
