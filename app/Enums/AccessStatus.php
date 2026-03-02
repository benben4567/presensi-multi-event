<?php

namespace App\Enums;

enum AccessStatus: string
{
    case Allowed = 'allowed';
    case Disabled = 'disabled';
    case Blacklisted = 'blacklisted';
}
