<?php

namespace App\Actions;

use App\Enums\ScanResultCode;
use App\Models\AttendanceLog;

readonly class AttendanceScanResult
{
    public function __construct(
        public string $outcome,
        public ScanResultCode $code,
        public string $message,
        public ?AttendanceLog $log = null,
    ) {}

    public function isAccepted(): bool
    {
        return $this->outcome === 'accepted';
    }

    public function isWarning(): bool
    {
        return $this->outcome === 'warning';
    }

    public function isRejected(): bool
    {
        return $this->outcome === 'rejected';
    }
}
