<?php

namespace App\Services\Letters;

use App\Models\NumberingCounter;
use App\Models\Team;
use Illuminate\Support\Facades\DB;

/**
 * Penomoran agenda surat masuk & nomor surat keluar per tim per tahun.
 *
 * Memakai tabel penghitung + lockForUpdate (bekerja di PostgreSQL/MySQL/SQLite),
 * sehingga dua pengguna yang menyimpan bersamaan tidak mendapat nomor sama,
 * dan nomor surat yang sudah dihapus tidak pernah dipakai ulang.
 */
class NumberingService
{
    public const INCOMING = 'incoming';

    public const OUTGOING = 'outgoing';

    /** Format bawaan nomor surat keluar. */
    public const DEFAULT_FORMAT = '{nomor}/{kode}/{bulan_romawi}/{tahun}';

    /**
     * Ambil nomor urut berikutnya. WAJIB dipanggil di dalam DB::transaction().
     */
    public function next(Team $team, string $type, int $year): int
    {
        $counter = NumberingCounter::withoutGlobalScopes()
            ->where(['team_id' => $team->id, 'type' => $type, 'year' => $year])
            ->lockForUpdate()
            ->first();

        if ($counter === null) {
            $counter = NumberingCounter::withoutGlobalScopes()->create([
                'team_id' => $team->id, 'type' => $type, 'year' => $year, 'last_number' => 0,
            ]);
        }

        $counter->last_number++;
        $counter->save();

        return $counter->last_number;
    }

    /**
     * Susun nomor surat keluar dari format tim.
     * Penanda: {nomor} {kode} {bulan} {bulan_romawi} {tahun} {singkatan}
     */
    public function formatOutgoing(Team $team, int $sequence, \DateTimeInterface $date, ?string $classificationCode): string
    {
        $format = (string) ($team->profileValue('numbering.outgoing_format') ?: self::DEFAULT_FORMAT);
        $digits = (int) ($team->profileValue('numbering.digits') ?: 3);

        $number = strtr($format, [
            '{nomor}' => str_pad((string) $sequence, $digits, '0', STR_PAD_LEFT),
            '{kode}' => $classificationCode ?: '-',
            '{bulan}' => $date->format('m'),
            '{bulan_romawi}' => self::roman((int) $date->format('n')),
            '{tahun}' => $date->format('Y'),
            '{singkatan}' => (string) ($team->profileValue('numbering.abbreviation') ?: strtoupper($team->slug)),
        ]);

        // Rapikan pemisah ganda bila ada penanda kosong.
        return trim((string) preg_replace('#/{2,}#', '/', $number), '/');
    }

    public static function roman(int $month): string
    {
        return ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][$month - 1] ?? (string) $month;
    }

    /** Pratinjau nomor berikutnya tanpa menghabiskan nomor (untuk form). */
    public function peekOutgoing(Team $team, \DateTimeInterface $date, ?string $classificationCode): string
    {
        $last = (int) NumberingCounter::withoutGlobalScopes()
            ->where(['team_id' => $team->id, 'type' => self::OUTGOING, 'year' => (int) $date->format('Y')])
            ->value('last_number');

        return $this->formatOutgoing($team, $last + 1, $date, $classificationCode);
    }

    /** Helper transaksi agar pemanggil tidak lupa membungkus. */
    public function transaction(callable $callback): mixed
    {
        return DB::transaction($callback);
    }
}
