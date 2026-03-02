<?php

namespace App\Enums;

enum AttendanceAction: string
{
    case CheckIn = 'check_in';
    case CheckOut = 'check_out';
}
