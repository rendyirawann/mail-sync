<?php

namespace App\Enums;

/**
 * Sifat surat.
 */
enum LetterNature: string
{
    case Biasa = 'biasa';
    case Segera = 'segera';
    case SangatSegera = 'sangat_segera';
    case Rahasia = 'rahasia';

    public function label(): string
    {
        return match ($this) {
            self::Biasa => 'Biasa',
            self::Segera => 'Segera',
            self::SangatSegera => 'Sangat Segera',
            self::Rahasia => 'Rahasia',
        };
    }

    /** Warna badge Bootstrap/Metronic. */
    public function badge(): string
    {
        return match ($this) {
            self::Biasa => 'secondary',
            self::Segera => 'warning',
            self::SangatSegera => 'danger',
            self::Rahasia => 'dark',
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
