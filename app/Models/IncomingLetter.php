<?php

namespace App\Models;

use App\Enums\IncomingStatus;
use App\Enums\LetterNature;
use App\Models\Concerns\BelongsToTeam;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Surat masuk beserta agenda & disposisinya.
 *
 * @property string $id
 * @property int $agenda_number
 * @property int $agenda_year
 * @property string $letter_number
 * @property \Illuminate\Support\Carbon $letter_date
 * @property \Illuminate\Support\Carbon $received_date
 * @property string $sender
 * @property string $subject
 * @property string|null $summary
 * @property LetterNature $nature
 * @property IncomingStatus $status
 * @property string|null $attachment_url
 */
class IncomingLetter extends Model
{
    use BelongsToTeam, HasUuids, SoftDeletes;

    protected $fillable = ['team_id', 'agenda_number', 'agenda_year', 'letter_number', 'letter_date', 'received_date', 'sender', 'contact_id', 'subject', 'summary', 'category_id', 'classification_id', 'nature', 'status', 'attachment_url', 'created_by'];

    protected function casts(): array
    {
        return [
            'letter_date' => 'date',
            'received_date' => 'date',
            'agenda_number' => 'integer',
            'agenda_year' => 'integer',
            'nature' => LetterNature::class,
            'status' => IncomingStatus::class,
        ];
    }

    /** @return BelongsTo<LetterCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(LetterCategory::class, 'category_id');
    }

    /** @return BelongsTo<LetterClassification, $this> */
    public function classification(): BelongsTo
    {
        return $this->belongsTo(LetterClassification::class, 'classification_id');
    }

    /** @return BelongsTo<Contact, $this> */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<Disposition, $this> */
    public function dispositions(): HasMany
    {
        return $this->hasMany(Disposition::class)->latest();
    }

    /** Nomor agenda tampilan, mis. "0007/2026". */
    public function agendaLabel(): string
    {
        return str_pad((string) $this->agenda_number, 4, '0', STR_PAD_LEFT).'/'.$this->agenda_year;
    }
}
