<?php

namespace App\Livewire;

use App\Models\PrintTemplate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Template Cetak')]
class AdminPrintTemplateIndex extends Component
{
    public function toggleActive(int $templateId): void
    {
        $template = PrintTemplate::findOrFail($templateId);
        $template->update(['is_active' => ! $template->is_active]);

        $this->dispatch('toast',
            message: $template->is_active ? 'Template diaktifkan.' : 'Template dinonaktifkan.',
            type: 'success',
        );
    }

    public function delete(int $templateId): void
    {
        $template = PrintTemplate::findOrFail($templateId);
        $template->delete();

        $this->dispatch('toast', message: 'Template berhasil dihapus.', type: 'success');
    }

    public function render(): \Illuminate\View\View
    {
        $templates = PrintTemplate::query()
            ->latest()
            ->get();

        return view('livewire.admin-print-template-index', compact('templates'));
    }
}
