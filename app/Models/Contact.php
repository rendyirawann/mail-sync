<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTeam;
use Illuminate\Database\Eloquent\Model;

/**
 * Instansi/pihak pengirim & penerima surat (tersimpan otomatis).
 *
 * @property int $id
 * @property string $name
 */
class Contact extends Model
{
    use BelongsToTeam;

    protected $fillable = ['team_id', 'name', 'address', 'phone', 'email'];
}
