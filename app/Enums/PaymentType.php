<?php

namespace App\Enums;

enum PaymentType: string
{
    case Registration = 'registration';
    case Renewal = 'renewal';
    case Upgrade = 'upgrade';

    public function label(): string
    {
        return match ($this) {
            self::Registration => 'Pendaftaran',
            self::Renewal => 'Perpanjangan',
            self::Upgrade => 'Upgrade ke Lifetime',
        };
    }
}
