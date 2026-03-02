<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

#[Layout('layouts.admin')]
#[Title('Activity Log')]
class AdminMonitoringActivity extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(): \Illuminate\View\View
    {
        $logs = Activity::query()
            ->with('causer')
            ->when($this->search, function ($q): void {
                $term = $this->search;
                $q->where(function ($q) use ($term): void {
                    $q->where('description', 'like', "%{$term}%")
                        ->orWhere('event', 'like', "%{$term}%")
                        ->orWhere('subject_type', 'like', "%{$term}%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate(25);

        return view('livewire.admin-monitoring-activity', compact('logs'));
    }
}
