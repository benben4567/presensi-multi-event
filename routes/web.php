<?php

use App\Http\Controllers\InvitationCardController;
use App\Http\Controllers\LaporanExportController;
use App\Http\Controllers\ProfileController;
use App\Livewire\AdminDashboard;
use App\Livewire\AdminEnrollmentList;
use App\Livewire\AdminEventForm;
use App\Livewire\AdminEventIndex;
use App\Livewire\AdminImportPeserta;
use App\Livewire\AdminLaporan;
use App\Livewire\AdminMonitoringActivity;
use App\Livewire\AdminMonitoringQueue;
use App\Livewire\AdminPresensi;
use App\Livewire\AdminPrintTemplateForm;
use App\Livewire\AdminPrintTemplateIndex;
use App\Livewire\AdminUserForm;
use App\Livewire\AdminUserIndex;
use App\Livewire\OpsEventManual;
use App\Livewire\OpsEventScan;
use App\Livewire\Panduan;
use App\Models\EventParticipant;
use Illuminate\Support\Facades\Route;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

// Root redirect to login
Route::get('/', fn () => redirect()->route('login'));

// Breeze internal redirect — forward ke halaman yang sesuai berdasarkan role
Route::get('/dashboard', function () {
    if (auth()->user()?->hasRole('admin')) {
        return redirect()->route('admin.dashboard');
    }

    if (auth()->user()?->hasRole('operator')) {
        return redirect()->route('ops.home');
    }

    return redirect()->route('login');
})->middleware(['auth', 'verified'])->name('dashboard');

// ─── Admin routes ──────────────────────────────────────────────────────────
Route::middleware(['auth', 'verified', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/dashboard', AdminDashboard::class)->name('dashboard');

        // Event management
        Route::get('/events', AdminEventIndex::class)->name('events.index');
        Route::get('/events/create', AdminEventForm::class)->name('events.create');
        Route::get('/events/{event}/edit', AdminEventForm::class)->name('events.edit');

        // Participant management
        Route::get('/events/{event}/participants', AdminEnrollmentList::class)->name('events.participants');
        Route::get('/events/{event}/participants/import', AdminImportPeserta::class)->name('events.participants.import');
        Route::get('/events/{event}/invitation-cards', [InvitationCardController::class, 'export'])->name('events.invitation-cards');
        Route::get('/events/{event}/invitation-cards/sticker', [InvitationCardController::class, 'exportStickerPdf'])->name('events.invitation-cards.sticker');
        Route::get('/events/{event}/invitation-cards/mapping', [InvitationCardController::class, 'exportStickerCsv'])->name('events.invitation-cards.mapping');
        Route::get('/events/{event}/participants/{eventParticipant}/card', [InvitationCardController::class, 'print'])->name('events.participants.card');

        Route::get('/events/{event}/participants/{eventParticipant}/qr', function (\App\Models\Event $event, EventParticipant $eventParticipant) {
            abort_if($eventParticipant->event_id !== $event->id, 404);
            $invitation = $eventParticipant->invitation;
            abort_if(! $invitation?->token, 404);
            $svg = QrCode::format('svg')->size(300)->errorCorrection('M')->generate('itsk:att:v1:'.$invitation->token);

            return response($svg)->header('Content-Type', 'image/svg+xml');
        })->name('events.participants.qr');

        Route::get('/presensi', AdminPresensi::class)->name('presensi.index');
        Route::get('/laporan', AdminLaporan::class)->name('laporan.index');
        Route::get('/laporan/export', LaporanExportController::class)->name('laporan.export');
        Route::get('/users', AdminUserIndex::class)->name('users.index');
        Route::get('/users/create', AdminUserForm::class)->name('users.create');
        Route::get('/users/{user}/edit', AdminUserForm::class)->name('users.edit');

        Route::get('/panduan', Panduan::class)->name('panduan');

        Route::get('/print-templates', AdminPrintTemplateIndex::class)->name('print-templates.index');
        Route::get('/print-templates/create', AdminPrintTemplateForm::class)->name('print-templates.create');
        Route::get('/print-templates/{template}/edit', AdminPrintTemplateForm::class)->name('print-templates.edit');

        Route::prefix('monitoring')->name('monitoring.')->group(function (): void {
            Route::get('/activity', AdminMonitoringActivity::class)->name('activity');
            Route::get('/queue', AdminMonitoringQueue::class)->name('queue');
        });
    });

// ─── Operator routes ────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:operator'])
    ->prefix('ops')
    ->name('ops.')
    ->group(function (): void {
        Route::get('/', fn () => view('ops.home'))->name('home');
        Route::get('/events/{event}/scan', OpsEventScan::class)->name('events.scan');
        Route::get('/events/{event}/manual', OpsEventManual::class)->name('events.manual');
        Route::get('/panduan', Panduan::class)->name('panduan');
    });

// ─── Profile (Breeze) ───────────────────────────────────────────────────────
Route::middleware('auth')->group(function (): void {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
