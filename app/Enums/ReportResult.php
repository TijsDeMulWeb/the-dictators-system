<?php

namespace App\Enums;

enum ReportResult: string
{
    case Win = 'win';
    case Loss = 'loss';

    public function label(): string
    {
        return match ($this) {
            self::Win => 'Win',
            self::Loss => 'Loss',
        };
    }
}
