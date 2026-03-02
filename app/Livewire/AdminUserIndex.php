<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
#[Title('Pengguna')]
class AdminUserIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function confirmDelete(string $userId): void
    {
        $this->dispatch('show-confirm',
            message: 'Hapus pengguna ini? Tindakan ini tidak dapat dibatalkan.',
            confirmedEvent: 'delete-user',
            confirmedData: ['userId' => $userId],
            confirmLabel: 'Hapus',
            cancelLabel: 'Batal',
        );
    }

    #[On('delete-user')]
    public function delete(string $userId): void
    {
        if ($userId === Auth::id()) {
            $this->dispatch('toast', message: 'Tidak dapat menghapus akun sendiri.', type: 'error');

            return;
        }

        $user = User::findOrFail($userId);
        $user->delete();

        $this->dispatch('toast', message: 'Pengguna berhasil dihapus.', type: 'success');
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin-user-index', [
            'users' => User::query()
                ->when($this->search, fn ($q) => $q->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                }))
                ->with('roles')
                ->latest()
                ->paginate(15),
        ]);
    }
}
