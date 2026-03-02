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

    public function save(): void
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($this->userId),
            ],
            'role' => ['required', Rule::in(['admin', 'operator'])],
        ];

        if ($this->userId === null) {
            $rules['password'] = ['required', 'string', 'min:8'];
        } else {
            $rules['password'] = ['nullable', 'string', 'min:8'];
        }

        $this->validate($rules);

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
