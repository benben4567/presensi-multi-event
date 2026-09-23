<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Profil')]
class ProfileEdit extends Component
{
    public string $name = '';

    public string $email = '';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $showDeleteForm = false;

    public string $deletePassword = '';

    public function mount(): void
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;
    }

    public function updateProfile(): void
    {
        $user = Auth::user();

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
        ]);

        $user->name = $this->name;

        if ($user->email !== $this->email) {
            $user->email = $this->email;
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('toast', message: 'Profil berhasil diperbarui.', type: 'success');
    }

    public function resendVerification(): void
    {
        $user = Auth::user();

        if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
            $this->dispatch('toast', message: 'Email verifikasi baru telah dikirim.', type: 'success');
        }
    }

    public function updatePassword(): void
    {
        $this->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        Auth::user()->update([
            'password' => Hash::make($this->password),
        ]);

        $this->reset(['current_password', 'password', 'password_confirmation']);

        $this->dispatch('toast', message: 'Kata sandi berhasil diperbarui.', type: 'success');
    }

    public function openDeleteForm(): void
    {
        $this->deletePassword = '';
        $this->resetErrorBag();
        $this->showDeleteForm = true;
    }

    public function cancelDeleteForm(): void
    {
        $this->showDeleteForm = false;
        $this->deletePassword = '';
    }

    public function deleteAccount(): void
    {
        $this->validate([
            'deletePassword' => ['required', 'current_password'],
        ], [], ['deletePassword' => 'kata sandi']);

        $user = Auth::user();

        Auth::logout();
        $user->delete();

        session()->invalidate();
        session()->regenerateToken();

        $this->redirect('/', navigate: false);
    }

    public function render(): \Illuminate\View\View
    {
        $layout = Auth::user()->hasRole('admin') ? 'layouts.admin' : 'layouts.ops';

        return view('livewire.profile-edit')->layout($layout, ['title' => 'Profil']);
    }
}
