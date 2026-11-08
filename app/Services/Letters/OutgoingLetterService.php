<?php

namespace App\Services\Letters;

use App\Enums\OutgoingStatus;
use App\Enums\TeamRole;
use App\Models\Contact;
use App\Models\LetterClassification;
use App\Models\OutgoingLetter;
use App\Models\Team;
use App\Models\User;
use App\Notifications\TeamNotification;
use App\Support\ActivityRecorder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Alur surat keluar: Draf → Menunggu persetujuan → Disetujui (nomor + QR terbit)
 * → Terkirim. "Perlu revisi" mengembalikan surat ke pembuat.
 *
 * Nomor surat baru diterbitkan saat disetujui, sehingga draf yang batal
 * tidak pernah "memakan" nomor.
 */
class OutgoingLetterService
{
    public function __construct(private readonly NumberingService $numbering) {}

    /**
     * @param  array<string, mixed>  $data  Data tervalidasi (termasuk 'content').
     */
    public function save(?OutgoingLetter $letter, User $user, array $data): OutgoingLetter
    {
        $letter ??= new OutgoingLetter(['status' => OutgoingStatus::Draf, 'created_by' => $user->id]);

        if ($letter->exists && ! $letter->isEditable()) {
            throw ValidationException::withMessages(['status' => 'Surat yang sudah diajukan/disetujui tidak dapat diubah.']);
        }

        $before = $letter->exists ? $letter->toArray() : [];
        $letter->fill($data + ['contact_id' => $this->rememberContact((string) $data['recipient'])]);
        $letter->status = OutgoingStatus::Draf;
        $letter->save();

        $before === []
            ? ActivityRecorder::created('surat keluar', "Membuat draf surat keluar: {$letter->subject}", $letter->toArray(), $letter)
            : ActivityRecorder::updated('surat keluar', "Mengubah draf surat keluar: {$letter->subject}", $before, $letter->toArray(), $letter);

        return $letter;
    }

    /** Ajukan ke pimpinan/owner untuk disetujui. */
    public function submit(Team $team, OutgoingLetter $letter, User $user): void
    {
        $this->expect($letter, [OutgoingStatus::Draf, OutgoingStatus::Ditolak]);

        $letter->update(['status' => OutgoingStatus::Menunggu, 'approval_note' => null]);
        ActivityRecorder::log('surat keluar', "Mengajukan persetujuan surat: {$letter->subject}", [], $letter);

        $team->users()
            ->whereIn('team_role', [TeamRole::Owner->value, TeamRole::Pimpinan->value])
            ->where('id', '!=', $user->id)
            ->where('is_active', true)
            ->get()
            ->each(fn (User $approver) => $approver->notify(new TeamNotification(
                'Menunggu persetujuan: '.$letter->subject,
                route('team.outgoing.show', $letter),
                'ki-check-square',
                'warning',
                $user->name,
            )));
    }

    /**
     * Setujui: terbitkan nomor surat (otomatis, kecuali diisi manual) dan token QR.
     */
    public function approve(Team $team, OutgoingLetter $letter, User $approver, ?string $manualNumber = null): void
    {
        $this->expect($letter, [OutgoingStatus::Menunggu, OutgoingStatus::Draf, OutgoingStatus::Ditolak]);

        DB::transaction(function () use ($team, $letter, $approver, $manualNumber): void {
            $date = Carbon::parse($letter->letter_date);

            if (filled($manualNumber)) {
                $letter->letter_number = trim((string) $manualNumber);
            } elseif (blank($letter->letter_number)) {
                $sequence = $this->numbering->next($team, NumberingService::OUTGOING, (int) $date->format('Y'));
                $code = $letter->classification_id ? LetterClassification::whereKey($letter->classification_id)->value('code') : null;
                $letter->sequence = $sequence;
                $letter->sequence_year = (int) $date->format('Y');
                $letter->letter_number = $this->numbering->formatOutgoing($team, $sequence, $date, $code);
            }

            $letter->fill([
                'status' => OutgoingStatus::Disetujui,
                'approver_id' => $approver->id,
                'approved_at' => now(),
                'verification_token' => $letter->verification_token ?: Str::random(32),
            ])->save();

            ActivityRecorder::log('surat keluar', "Menyetujui surat {$letter->letter_number}: {$letter->subject}", [], $letter);
        });

        $this->notifyCreator($letter, $approver, 'Surat disetujui: '.$letter->letter_number, 'ki-check-circle', 'success');
    }

    public function reject(OutgoingLetter $letter, User $approver, string $note): void
    {
        $this->expect($letter, [OutgoingStatus::Menunggu]);

        $letter->update(['status' => OutgoingStatus::Ditolak, 'approval_note' => $note, 'approver_id' => $approver->id]);
        ActivityRecorder::log('surat keluar', "Mengembalikan surat untuk revisi: {$letter->subject}", ['catatan' => $note], $letter);

        $this->notifyCreator($letter, $approver, 'Perlu revisi: '.$letter->subject, 'ki-information-5', 'danger');
    }

    public function markSent(OutgoingLetter $letter): void
    {
        $this->expect($letter, [OutgoingStatus::Disetujui]);
        $letter->update(['status' => OutgoingStatus::Terkirim, 'sent_at' => now()]);
        ActivityRecorder::log('surat keluar', "Menandai surat {$letter->letter_number} terkirim", [], $letter);
    }

    public function delete(OutgoingLetter $letter): void
    {
        ActivityRecorder::deleted('surat keluar', "Menghapus surat keluar: {$letter->subject}", $letter->toArray(), $letter);
        $letter->delete();
    }

    /** @param list<OutgoingStatus> $allowed */
    private function expect(OutgoingLetter $letter, array $allowed): void
    {
        if (! in_array($letter->status, $allowed, true)) {
            throw ValidationException::withMessages(['status' => "Aksi ini tidak berlaku untuk surat berstatus {$letter->status->label()}."]);
        }
    }

    private function notifyCreator(OutgoingLetter $letter, User $actor, string $title, string $icon, string $color): void
    {
        $creator = $letter->creator;

        if ($creator !== null && $creator->isNot($actor)) {
            $creator->notify(new TeamNotification($title, route('team.outgoing.show', $letter), $icon, $color, $actor->name));
        }
    }

    private function rememberContact(string $name): ?int
    {
        $name = trim($name);

        return $name === '' ? null : Contact::firstOrCreate(['name' => $name])->id;
    }
}
