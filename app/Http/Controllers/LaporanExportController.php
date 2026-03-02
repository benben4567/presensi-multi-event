<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceAction;
use App\Models\AttendanceLog;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\EventSession;
use Illuminate\Http\Request;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanExportController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        $request->validate([
            'event_id' => ['required', 'string', 'exists:events,id'],
            'session_id' => ['required', 'integer', 'exists:event_sessions,id'],
        ]);

        $event = Event::findOrFail($request->string('event_id'));
        $session = EventSession::findOrFail($request->integer('session_id'));

        $enrollments = EventParticipant::query()
            ->with('participant')
            ->where('event_id', $event->id)
            ->orderBy('id')
            ->get();

        $rows = $enrollments->map(function (EventParticipant $enrollment) use ($session): array {
            $logs = AttendanceLog::where('event_participant_id', $enrollment->id)
                ->where('session_id', $session->id)
                ->get(['action', 'scanned_at']);

            $checkIn = $logs->firstWhere('action', AttendanceAction::CheckIn)?->scanned_at;
            $checkOut = $logs->firstWhere('action', AttendanceAction::CheckOut)?->scanned_at;

            return [
                'Nama' => $enrollment->participant->name,
                'No HP' => $enrollment->participant->phone_e164 ?? '',
                'Check-In' => $checkIn?->format('d/m/Y H:i:s') ?? '',
                'Check-Out' => $checkOut?->format('d/m/Y H:i:s') ?? '',
                'Status' => $checkIn ? 'Hadir' : 'Tidak Hadir',
            ];
        });

        $filename = 'laporan-presensi-'.str($event->name)->slug().'-'.str($session->name)->slug().'.xlsx';

        return (new FastExcel($rows))->download($filename);
    }
}
