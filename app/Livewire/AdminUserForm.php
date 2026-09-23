<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Pengguna')]
class AdminUserForm extends Component
{
    public ?string $userId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $role = 'operator';

    public function mount(?User $user = null): void
    {
        if ($user && $user->exists) {
            $this->userId = $user->id;
            $this->name = $user->name;
            $this->email = $user->email;
            $this->role = $user->roles->first()?->name ?? 'operator';
        }
    }

    /** @return array<string, array<int, mixed>> */
    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($this->userId),
            ],
            'role' => ['required', Rule::in(['admin', 'operator'])],
            'password' => $this->userId === null
                ? ['required', 'string', 'min:8']
                : ['nullable', 'string', 'min:8'],
        ];
    }

    /**
     * Validate a single field as soon as the user leaves it (wire:model.blur),
     * instead of only surfacing errors after clicking "Simpan".
     */
    public function updated(string $field): void
    {
        if (array_key_exists($field, $this->rules())) {
            $this->validateOnly($field, $this->rules());
        }
    }

    public function save(): void
    {
        $this->validate($this->rules());

        if ($this->userId) {
            $user = User::findOrFail($this->userId);

            $user->update(array_filter([
                'name' => $this->name,
                'email' => $this->email,
                'password' => $this->password ?: null,
            ]));

            if ($this->userId !== Auth::id()) {
                $user->syncRoles($this->role);
            }

            $this->dispatch('toast', message: 'Pengguna berhasil diperbarui.', type: 'success');
        } else {
            $user = User::create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => $this->password,
            ]);

            $user->assignRole($this->role);

            $this->dispatch('toast', message: 'Pengguna berhasil dibuat.', type: 'success');
        }

        $this->redirect(route('admin.users.index'), navigate: true);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin-user-form');
    }
}
