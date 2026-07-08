<?php

namespace App\Enums;

enum ChallengeTier: string
{
    case Protector = 'protector';
    case Gold = 'gold';
    case Silver = 'silver';
    case Bronze = 'bronze';
    case Mikkim = 'mikkim';
    case Brown = 'brown';

    /**
     * Fixed bonus points awarded to each participant for this tier.
     */
    public function points(): int
    {
        return match ($this) {
            self::Protector => 800,
            self::Gold => 500,
            self::Silver => 400,
            self::Bronze => 300,
            self::Mikkim => 250,
            self::Brown => 200,
        };
    }

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
