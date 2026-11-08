<?php

namespace App\Support\Tenancy;

use App\Models\Team;

/**
 * Menyimpan tim aktif untuk request ini (diisi middleware ResolveTeam).
 * Didaftarkan "scoped" sehingga aman untuk Octane.
 */
class CurrentTeam
{
    private ?Team $team = null;

    public function set(?Team $team): void
    {
        $this->team = $team;
    }

    public function get(): ?Team
    {
        return $this->team;
    }

    public function id(): ?string
    {
        return $this->team?->getKey();
    }

    public function check(): bool
    {
        return $this->team !== null;
    }
}
