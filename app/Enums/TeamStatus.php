<?php

namespace App\Enums;

/**
 * Status langganan tim.
 * Frozen: masa aktif habis — hanya bisa membaca & mengekspor data.
 * Suspended: dinonaktifkan Superadmin — tidak bisa login.
 */
enum TeamStatus: string
{
    case Active = 'active';
    case Frozen = 'frozen';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Frozen => 'Beku',
            self::Suspended => 'Dinonaktifkan',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Frozen => 'warning',
            self::Suspended => 'danger',
        };
    }
}
