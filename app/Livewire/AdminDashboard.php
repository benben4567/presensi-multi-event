<?php

namespace App\Livewire;

use App\Enums\ScanResultCode;
use App\Models\AttendanceLog;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\ScanAttempt;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Activitylog\Models\Activity;

#[Layout('layouts.admin')]
#[Title('Dashboard')]
class AdminDashboard extends Component
{
    public function render(): \Illuminate\View\View
    {
        $today = now()->toDateString();

        // ── 1. Active events today ───────────────────────────────────────────
        $activeEvents = Event::where('status', 'open')
            ->whereDate('start_at', '<=', $today)
            ->whereDate('end_at', '>=', $today)
            ->orderBy('start_at')
            ->get();

        $activeEventIds = $activeEvents->pluck('id');

        // ── 2. Today's sessions for active events ────────────────────────────
        $todaySessions = $activeEventIds->isNotEmpty()
            ? EventSession::whereIn('event_id', $activeEventIds)
                ->whereDate('start_at', $today)
                ->get(['id', 'event_id', 'name'])
            : collect();

        $todaySessionIds = $todaySessions->pluck('id');
        $todaySessionByEvent = $todaySessions->keyBy('event_id');

        // ── 3. Check-in aggregation per event (today's sessions only) ────────
        $checkinByEvent = $todaySessionIds->isNotEmpty()
            ? AttendanceLog::whereIn('event_id', $activeEventIds)
                ->whereIn('session_id', $todaySessionIds)
                ->where('action', 'check_in')
                ->selectRaw('event_id, COUNT(*) as total')
                ->groupBy('event_id')
                ->pluck('total', 'event_id')
            : collect();

        $snapshotCheckins = (int) $checkinByEvent->sum();

        // ── 4. Scan attempt aggregation per event + result ───────────────────
        $scanByEventResult = $activeEventIds->isNotEmpty()
            ? ScanAttempt::whereIn('event_id', $activeEventIds)
                ->whereDate('scanned_at', $today)
                ->selectRaw('event_id, result, COUNT(*) as total')
                ->groupBy('event_id', 'result')
                ->get()
                ->groupBy('event_id')
            : collect();

        $snapshotWarnings = 0;
        $snapshotRejected = 0;

        foreach ($scanByEventResult as $metrics) {
            foreach ($metrics as $m) {
                if ($m->result === 'warning') {
                    $snapshotWarnings += $m->total;
                }

                if ($m->result === 'rejected') {
                    $snapshotRejected += $m->total;
                }
            }
        }

        // ── 5. Hourly trend — 24 buckets ─────────────────────────────────────
        $hourlyData = array_fill(0, 24, ['accepted' => 0, 'warning' => 0, 'rejected' => 0]);

        if ($activeEventIds->isNotEmpty()) {
            $hourExpr = DB::getDriverName() === 'sqlite'
                ? "CAST(strftime('%H', scanned_at) AS INTEGER)"
                : 'HOUR(scanned_at)';

            ScanAttempt::whereIn('event_id', $activeEventIds)
                ->whereDate('scanned_at', $today)
                ->selectRaw("{$hourExpr} as hour, result, COUNT(*) as total")
                ->groupBy('hour', 'result')
                ->get()
                ->each(function ($row) use (&$hourlyData): void {
                    $hourlyData[(int) $row->hour][$row->result] = (int) $row->total;
                });
        }

        $hourlyMax = max(1, ...array_map(
            fn ($b) => $b['accepted'] + $b['warning'] + $b['rejected'],
            $hourlyData
        ));

        // ── 6. Top 5 rejection reasons ───────────────────────────────────────
        $topRejected = collect();

        if ($activeEventIds->isNotEmpty() && $snapshotRejected > 0) {
            $topRejected = ScanAttempt::whereIn('event_id', $activeEventIds)
                ->where('result', 'rejected')
                ->whereDate('scanned_at', $today)
                ->selectRaw('code, COUNT(*) as total')
                ->groupBy('code')
                ->orderByDesc('total')
                ->limit(5)
                ->get();
        }

        // ── 7. Override active events ─────────────────────────────────────────
        $overrideEvents = Event::where('override_until', '>', now())
            ->orderBy('override_until')
            ->get(['id', 'name', 'override_until']);

        // ── 8. Top 3 revoked scan reasons ────────────────────────────────────
        $topRevoked = collect();

        if ($activeEventIds->isNotEmpty()) {
            $topRevoked = ScanAttempt::whereIn('event_id', $activeEventIds)
                ->where('result', 'rejected')
                ->where('code', ScanResultCode::TokenRevoked->value)
                ->whereDate('scanned_at', $today)
                ->selectRaw('message, COUNT(*) as total')
                ->groupBy('message')
                ->orderByDesc('total')
                ->limit(3)
                ->get();
        }

        // ── 9. Monitoring mini ────────────────────────────────────────────────
        $activityLogCount = Activity::whereDate('created_at', $today)->count();
        $errorLogCount = $this->countTodayErrors();

        return view('livewire.admin-dashboard', [
            'activeEvents' => $activeEvents,
            'todaySessionByEvent' => $todaySessionByEvent,
            'snapshotCheckins' => $snapshotCheckins,
            'snapshotWarnings' => $snapshotWarnings,
            'snapshotRejected' => $snapshotRejected,
            'hourlyData' => $hourlyData,
            'hourlyMax' => $hourlyMax,
            'topRejected' => $topRejected,
            'checkinByEvent' => $checkinByEvent,
            'scanByEventResult' => $scanByEventResult,
            'overrideEvents' => $overrideEvents,
            'topRevoked' => $topRevoked,
            'activityLogCount' => $activityLogCount,
            'errorLogCount' => $errorLogCount,
        ]);
    }

    private function countTodayErrors(): int
    {
        $logFile = storage_path('logs/laravel.log');

        if (! file_exists($logFile)) {
            return 0;
        }

        $today = now()->format('Y-m-d');
        $count = 0;
        $handle = fopen($logFile, 'r');

        if ($handle === false) {
            return 0;
        }

        while (($line = fgets($handle)) !== false) {
            if (str_contains($line, "[$today") &&
                preg_match('/\.(ERROR|CRITICAL|ALERT|EMERGENCY)/', $line)) {
                $count++;
            }
        }

        fclose($handle);

        return $count;
    }
}
