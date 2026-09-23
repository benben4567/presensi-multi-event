<?php

namespace Tests\Feature;

use App\Livewire\ProfileEdit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ProfileEdit::class)
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->call('updateProfile')
            ->assertHasNoErrors();

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ProfileEdit::class)
            ->set('name', 'Test User')
            ->set('email', $user->email)
            ->call('updateProfile')
            ->assertHasNoErrors();

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ProfileEdit::class)
            ->set('deletePassword', 'password')
            ->call('deleteAccount');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ProfileEdit::class)
            ->set('deletePassword', 'wrong-password')
            ->call('deleteAccount')
            ->assertHasErrors('deletePassword');

        $this->assertNotNull($user->fresh());
    }

    public function test_password_can_be_updated(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ProfileEdit::class)
            ->set('current_password', 'password')
            ->set('password', 'new-password-123')
            ->set('password_confirmation', 'new-password-123')
            ->call('updatePassword')
            ->assertHasNoErrors();

        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('new-password-123', $user->fresh()->password));
    }

    public function test_current_password_must_be_correct_to_update_password(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ProfileEdit::class)
            ->set('current_password', 'wrong-password')
            ->set('password', 'new-password-123')
            ->set('password_confirmation', 'new-password-123')
            ->call('updatePassword')
            ->assertHasErrors('current_password');
    }

    public function test_validation_errors_are_shown_in_indonesian(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ProfileEdit::class)
            ->set('name', '')
            ->set('email', '')
            ->call('updateProfile')
            ->assertHasErrors('name');
    }

    public function test_admin_gets_admin_layout(): void
    {
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // "Pengguna" only appears in the admin sidebar (layouts.admin).
        $this->actingAs($admin)
            ->get('/profile')
            ->assertOk()
            ->assertSeeText('Pengguna');
    }

    public function test_operator_gets_ops_layout(): void
    {
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'operator', 'guard_name' => 'web']);
        $operator = User::factory()->create();
        $operator->assignRole('operator');

        $this->actingAs($operator)
            ->get('/profile')
            ->assertOk();
    }
}
