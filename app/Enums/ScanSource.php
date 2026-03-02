<?php

namespace App\Enums;

enum ScanSource: string
{
    case Qr = 'qr';
    case Manual = 'manual';
}
