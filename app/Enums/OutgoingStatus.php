<?php

namespace App\Enums;

/**
 * Alur surat keluar: Draf → Menunggu Persetujuan → Disetujui/Ditolak → Terkirim.
 */
enum OutgoingStatus: string
{
    case Draf = 'draf';
    case Menunggu = 'menunggu';
    case Disetujui = 'disetujui';
    case Ditolak = 'ditolak';
    case Terkirim = 'terkirim';

    public function label(): string
    {
        return match ($this) {
            self::Draf => 'Draf',
            self::Menunggu => 'Menunggu Persetujuan',
            self::Disetujui => 'Disetujui',
            self::Ditolak => 'Perlu Revisi',
            self::Terkirim => 'Terkirim',
        };
    }

    /** Warna badge Bootstrap/Metronic. */
    public function badge(): string
    {
        return match ($this) {
            self::Draf => 'secondary',
            self::Menunggu => 'warning',
            self::Disetujui => 'info',
            self::Ditolak => 'danger',
            self::Terkirim => 'success',
        };
    }

    /** @return array<string, string> nilai => label, untuk <select>. */
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = $case->label();
        }

        return $out;
    }
}
