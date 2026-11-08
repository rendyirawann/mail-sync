<?php

namespace App\Services\Letters;

use App\Enums\DispositionStatus;
use App\Enums\IncomingStatus;
use App\Models\Disposition;
use App\Models\Employee;
use App\Models\IncomingLetter;
use App\Models\User;
use App\Notifications\TeamNotification;
use App\Support\ActivityRecorder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Alur disposisi: kirim ke satu/lebih pegawai → penerima memproses →
 * selesai. Status surat masuk ikut bergerak otomatis.
 */
class DispositionService
{
    /**
     * @param  list<string>  $employeeIds
     * @return Collection<int, Disposition>
     */
    public function dispatch(IncomingLetter $letter, User $from, array $employeeIds, string $instruction, ?string $dueDate): Collection
    {
        return DB::transaction(function () use ($letter, $from, $employeeIds, $instruction, $dueDate): Collection {
            $recipients = Employee::query()->with('user')->whereIn('id', $employeeIds)->get();

            $created = $recipients->map(fn (Employee $employee) => Disposition::create([
                'incoming_letter_id' => $letter->id,
                'from_user_id' => $from->id,
                'to_employee_id' => $employee->id,
                'instruction' => $instruction,
                'due_date' => $dueDate,
                'status' => DispositionStatus::Baru,
            ]));

            if ($letter->status === IncomingStatus::Baru) {
                $letter->update(['status' => IncomingStatus::Didisposisi]);
            }

            ActivityRecorder::log('disposisi', 'Mendisposisikan surat '.$letter->agendaLabel().' kepada '.$recipients->pluck('name')->join(', '), [], $letter);

            foreach ($recipients as $employee) {
                $employee->user?->notify(new TeamNotification(
                    'Disposisi baru: '.$letter->subject,
                    route('team.incoming.show', $letter),
                    'ki-send',
                    $letter->nature->value === 'biasa' ? 'primary' : 'danger',
                    $from->name,
                ));
            }

            return $created;
        });
    }

    public function respond(Disposition $disposition, User $by, DispositionStatus $status, ?string $response): void
    {
        DB::transaction(function () use ($disposition, $by, $status, $response): void {
            $disposition->fill([
                'status' => $status,
                'response' => $response ?? $disposition->response,
                'read_at' => $disposition->read_at ?? now(),
                'completed_at' => $status === DispositionStatus::Selesai ? now() : null,
            ])->save();

            $letter = $disposition->letter;

            // Surat ikut "Diproses" saat ada yang mulai mengerjakan, "Selesai" bila semua disposisi selesai.
            if ($status !== DispositionStatus::Baru && $letter->status === IncomingStatus::Didisposisi) {
                $letter->update(['status' => IncomingStatus::Diproses]);
            }
            if ($letter->dispositions()->where('status', '!=', DispositionStatus::Selesai->value)->doesntExist()) {
                $letter->update(['status' => IncomingStatus::Selesai]);
            }

            ActivityRecorder::log('disposisi', "Memperbarui disposisi surat {$letter->agendaLabel()} menjadi {$status->label()}", [], $letter);

            if ($status === DispositionStatus::Selesai && $disposition->sender && $disposition->sender->isNot($by)) {
                $disposition->sender->notify(new TeamNotification(
                    'Disposisi selesai: '.$letter->subject,
                    route('team.incoming.show', $letter),
                    'ki-check-circle',
                    'success',
                    $by->name,
                ));
            }
        });
    }

    /** Penerima disposisi (pegawai yang tertaut ke akun ini) atau pengelola surat. */
    public function canRespond(Disposition $disposition, User $user): bool
    {
        return $disposition->recipient?->user_id === $user->id || $user->can('team.dispositions.create');
    }
}
