<?php

namespace App\Enums;

enum EventStatus: string
{
    case Draft = 'draft';
    case Open = 'open';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Open => 'Aktif',
            self::Closed => 'Selesai',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Open => 'green',
            self::Closed => 'red',
        };
    }
}
