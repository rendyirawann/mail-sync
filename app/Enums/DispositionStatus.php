<?php

namespace App\Enums;

/**
 * Status tindak lanjut disposisi.
 */
enum DispositionStatus: string
{
    case Baru = 'baru';
    case Diproses = 'diproses';
    case Selesai = 'selesai';

    public function label(): string
    {
        return match ($this) {
            self::Baru => 'Belum Dibaca',
            self::Diproses => 'Diproses',
            self::Selesai => 'Selesai',
        };
    }

    /** Warna badge Bootstrap/Metronic. */
    public function badge(): string
    {
        return match ($this) {
            self::Baru => 'primary',
            self::Diproses => 'warning',
            self::Selesai => 'success',
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
