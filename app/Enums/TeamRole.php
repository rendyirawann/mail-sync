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
     * Matriks hak akses tim — satu-satunya sumber aturan peran.
     * Didaftarkan sebagai Gate "team.{ability}" di AppServiceProvider.
     */
    public const ABILITIES = [
        'letters.manage' => [self::Owner, self::Admin, self::Staf],       // catat & ubah surat
        'letters.delete' => [self::Owner, self::Admin],
        'dispositions.create' => [self::Owner, self::Admin, self::Pimpinan],
        'outgoing.approve' => [self::Owner, self::Pimpinan],
        'master.manage' => [self::Owner, self::Admin],
        'templates.manage' => [self::Owner, self::Admin],
        'members.manage' => [self::Owner],
        'settings.manage' => [self::Owner, self::Admin],
        'modules.manage' => [self::Owner],
        'billing.manage' => [self::Owner],
        'optional.manage' => [self::Owner, self::Admin, self::Staf],
    ];

    public function can(string $ability): bool
    {
        return in_array($this, self::ABILITIES[$ability] ?? [], true);
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
