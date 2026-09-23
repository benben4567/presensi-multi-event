<?php

namespace App\Support;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

class PhoneNumberNormalizer
{
    public static function toE164(string $raw): ?string
    {
        if ($raw === '') {
            return null;
        }

        try {
            $util = PhoneNumberUtil::getInstance();
            $number = $util->parse($raw, 'ID');

            if (! $util->isValidNumber($number)) {
                return null;
            }

            return $util->format($number, PhoneNumberFormat::E164);
        } catch (NumberParseException) {
            return null;
        }
    }
}
