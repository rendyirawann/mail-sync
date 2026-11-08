<?php

namespace App\Models;

use App\Enums\LetterNature;
use App\Enums\OutgoingStatus;
use App\Models\Concerns\BelongsToTeam;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Surat keluar: dibuat dari template (atau manual), disetujui pimpinan, diberi nomor & QR verifikasi.
 *
 * @property string $id
 * @property string|null $letter_number
 * @property \Illuminate\Support\Carbon $letter_date
 * @property string $recipient
 * @property string $subject
 * @property LetterNature $nature
 * @property OutgoingStatus $status
 * @property array<string, string>|null $content
 * @property string|null $template_id
 * @property string|null $verification_token
 * @property-read LetterTemplate|null $template
 * @property-read Employee|null $signer
 */
class OutgoingLetter extends Model
{
    use BelongsToTeam, HasUuids, SoftDeletes;

    protected $fillable = ['team_id', 'letter_number', 'sequence', 'sequence_year', 'letter_date', 'recipient', 'contact_id', 'subject', 'category_id', 'classification_id', 'nature', 'template_id', 'content', 'signer_employee_id', 'status', 'approver_id', 'approved_at', 'approval_note', 'sent_at', 'attachment_url', 'verification_token', 'created_by'];

    protected function casts(): array
    {
        return [
            'letter_date' => 'date',
            'approved_at' => 'datetime',
            'sent_at' => 'datetime',
            'content' => 'array',
            'nature' => LetterNature::class,
            'status' => OutgoingStatus::class,
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

    /** @return BelongsTo<LetterTemplate, $this> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(LetterTemplate::class, 'template_id');
    }

    /** @return BelongsTo<Employee, $this> */
    public function signer(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'signer_employee_id');
    }

    /** @return BelongsTo<User, $this> */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<Contact, $this> */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /** Boleh diedit hanya selama draf / perlu revisi. */
    public function isEditable(): bool
    {
        return in_array($this->status, [OutgoingStatus::Draf, OutgoingStatus::Ditolak], true);
    }
}
