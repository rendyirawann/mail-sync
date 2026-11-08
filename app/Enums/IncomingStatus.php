<?php

namespace App\Enums;

/**
 * Alur status surat masuk: Baru → Didisposisi → Diproses → Selesai → Diarsipkan.
 */
enum IncomingStatus: string
{
    case Baru = 'baru';
    case Didisposisi = 'didisposisi';
    case Diproses = 'diproses';
    case Selesai = 'selesai';
    case Diarsipkan = 'diarsipkan';

    public function label(): string
    {
        return match ($this) {
            self::Baru => 'Baru',
            self::Didisposisi => 'Didisposisi',
            self::Diproses => 'Diproses',
            self::Selesai => 'Selesai',
            self::Diarsipkan => 'Diarsipkan',
        };
    }

    /** Warna badge Bootstrap/Metronic. */
    public function badge(): string
    {
        return match ($this) {
            self::Baru => 'primary',
            self::Didisposisi => 'info',
            self::Diproses => 'warning',
            self::Selesai => 'success',
            self::Diarsipkan => 'secondary',
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
