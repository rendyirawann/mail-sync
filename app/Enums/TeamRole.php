<?php

namespace App\Enums;

/**
 * Peran akun di dalam tim. Kuota akun (termasuk owner) diatur per paket.
 */
enum TeamRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Pimpinan = 'pimpinan';
    case Staf = 'staf';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Admin => 'Admin',
            self::Pimpinan => 'Pimpinan',
            self::Staf => 'Staf',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Owner => 'Akses penuh termasuk tagihan, anggota, dan modul.',
            self::Admin => 'Kelola surat, disposisi, master data, dan laporan.',
            self::Pimpinan => 'Memberi disposisi dan menyetujui surat keluar.',
            self::Staf => 'Mencatat surat dan menindaklanjuti disposisi.',
        };
    }

    /**
     * Peran yang bisa dipilih owner saat menambah anggota.
     *
     * @return list<self>
     */
    public static function assignable(): array
    {
        return [self::Admin, self::Pimpinan, self::Staf];
    }
}
