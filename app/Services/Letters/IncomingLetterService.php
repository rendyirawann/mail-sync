<?php

namespace App\Services\Letters;

use App\Enums\IncomingStatus;
use App\Models\Contact;
use App\Models\IncomingLetter;
use App\Models\Team;
use App\Models\User;
use App\Support\ActivityRecorder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Pencatatan surat masuk: nomor agenda otomatis per tahun, kontak pengirim
 * tersimpan otomatis untuk isian berikutnya, dan jejak audit.
 */
class IncomingLetterService
{
    public function __construct(private readonly NumberingService $numbering) {}

    /**
     * @param  array<string, mixed>  $data  Data tervalidasi dari StoreIncomingLetterRequest.
     */
    public function create(Team $team, User $user, array $data): IncomingLetter
    {
        return DB::transaction(function () use ($team, $user, $data): IncomingLetter {
            $year = (int) Carbon::parse($data['received_date'])->format('Y');

            $letter = IncomingLetter::create($data + [
                'agenda_number' => $this->numbering->next($team, NumberingService::INCOMING, $year),
                'agenda_year' => $year,
                'contact_id' => $this->rememberContact($data['sender']),
                'status' => IncomingStatus::Baru,
                'created_by' => $user->id,
            ]);

            ActivityRecorder::created('surat masuk', "Mencatat surat masuk {$letter->agendaLabel()}: {$letter->subject}", $letter->toArray(), $letter);

            return $letter;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(IncomingLetter $letter, array $data): IncomingLetter
    {
        $before = $letter->toArray();
        $letter->fill($data + ['contact_id' => $this->rememberContact($data['sender'])])->save();

        ActivityRecorder::updated('surat masuk', "Mengubah surat masuk {$letter->agendaLabel()}", $before, $letter->toArray(), $letter);

        return $letter;
    }

    public function changeStatus(IncomingLetter $letter, IncomingStatus $status): void
    {
        $letter->update(['status' => $status]);
        ActivityRecorder::log('surat masuk', "Mengubah status surat {$letter->agendaLabel()} menjadi {$status->label()}", [], $letter);
    }

    public function delete(IncomingLetter $letter): void
    {
        ActivityRecorder::deleted('surat masuk', "Menghapus surat masuk {$letter->agendaLabel()}: {$letter->subject}", $letter->toArray(), $letter);
        $letter->delete();
    }

    /** Simpan pengirim sebagai kontak (dipakai untuk saran isian berikutnya). */
    private function rememberContact(string $name): ?int
    {
        $name = trim($name);

        return $name === '' ? null : Contact::firstOrCreate(['name' => $name])->id;
    }
}
