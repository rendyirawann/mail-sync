<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTeam;
use Illuminate\Database\Eloquent\Model;

/**
 * Jenis/kategori surat (Undangan, Pemberitahuan, Permohonan, ...).
 *
 * @property int $id
 * @property string $name
 */
class LetterCategory extends Model
{
    use BelongsToTeam;

    protected $fillable = ['team_id', 'name'];
}
