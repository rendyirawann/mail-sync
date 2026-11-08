<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTeam;
use Illuminate\Database\Eloquent\Model;

/**
 * Penghitung nomor agenda/surat per tim, jenis, dan tahun.
 *
 * @property int $last_number
 */
class NumberingCounter extends Model
{
    use BelongsToTeam;

    protected $fillable = ['team_id', 'type', 'year', 'last_number'];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'last_number' => 'integer',
        ];
    }
}
