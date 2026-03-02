<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use romanzipp\QueueMonitor\Enums\MonitorStatus;
use romanzipp\QueueMonitor\Services\QueueMonitor;

#[Layout('layouts.admin')]
#[Title('Queue Monitor')]
class AdminMonitoringQueue extends Component
{
    use WithPagination;

    public ?int $status = null;

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function render(): \Illuminate\View\View
    {
        $jobs = QueueMonitor::getModel()
            ->newQuery()
            ->when($this->status !== null, fn ($q) => $q->where('status', $this->status))
            ->orderByDesc('started_at')
            ->paginate(25);

        $statuses = [
            MonitorStatus::RUNNING => 'Berjalan',
            MonitorStatus::SUCCEEDED => 'Berhasil',
            MonitorStatus::FAILED => 'Gagal',
            MonitorStatus::STALE => 'Stale',
            MonitorStatus::QUEUED => 'Antre',
        ];

        return view('livewire.admin-monitoring-queue', compact('jobs', 'statuses'));
    }
}
