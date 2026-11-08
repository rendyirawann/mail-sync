<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTeam;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modul opsional: monitoring honorarium.
 *
 * @property string $id
 * @property \Illuminate\Support\Carbon $period
 * @property int $gross_amount
 * @property int $tax_percent
 * @property int $net_amount
 * @property string $status
 * @property-read Employee $employee
 */
class Honorarium extends Model
{
    use BelongsToTeam, HasUuids;

    protected $fillable = ['team_id', 'employee_id', 'activity', 'period', 'gross_amount', 'tax_percent', 'net_amount', 'attachment_url', 'status'];

    protected function casts(): array
    {
        return [
            'period' => 'date',
            'gross_amount' => 'integer',
            'tax_percent' => 'integer',
            'net_amount' => 'integer',
        ];
    }

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
