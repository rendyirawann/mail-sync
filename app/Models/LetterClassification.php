<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTeam;
use Illuminate\Database\Eloquent\Model;

/**
 * Kode klasifikasi surat (mis. 000 Umum, 800 Kepegawaian).
 *
 * @property int $id
 * @property string $code
 * @property string $name
 */
class LetterClassification extends Model
{
    use BelongsToTeam;

    protected $fillable = ['team_id', 'code', 'name'];
}
