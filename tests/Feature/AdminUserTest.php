<?php

namespace Tests\Feature;

use App\Livewire\AdminUserForm;
use App\Livewire\AdminUserIndex;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminUserTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'operator', 'guard_name' => 'web']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    // ── Access control ────────────────────────────────────────────────────────

    #[Test]
    public function admin_can_access_user_index(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.users.index'))
            ->assertOk();
    }

    #[Test]
    public function guest_is_redirected_from_user_index(): void
    {
        $this->get(route('admin.users.index'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function operator_cannot_access_user_index(): void
    {
        $operator = User::factory()->create();
        $operator->assignRole('operator');

        $this->actingAs($operator)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    // ── Index listing ─────────────────────────────────────────────────────────

    #[Test]
    public function user_index_shows_all_users(): void
    {
        $operator = User::factory()->create(['name' => 'Budi Operator']);
        $operator->assignRole('operator');

        Livewire::actingAs($this->admin)
            ->test(AdminUserIndex::class)
            ->assertSee('Budi Operator');
    }

    #[Test]
    public function user_index_search_filters_by_name_and_email(): void
    {
        $sari = User::factory()->create(['name' => 'Sari Operator', 'email' => 'sari@test.com']);
        $sari->assignRole('operator');

        $budi = User::factory()->create(['name' => 'Budi Lain']);
        $budi->assignRole('operator');

        Livewire::actingAs($this->admin)
            ->test(AdminUserIndex::class)
            ->set('search', 'Sari')
            ->assertSee('Sari Operator')
            ->assertDontSee('Budi Lain');
    }

    // ── Create ────────────────────────────────────────────────────────────────

    #[Test]
    public function admin_can_create_operator_user(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminUserForm::class)
            ->set('name', 'Operator Baru')
            ->set('email', 'operator.baru@test.com')
            ->set('password', 'password123')
            ->set('role', 'operator')
            ->call('save');

        $this->assertDatabaseHas('users', ['email' => 'operator.baru@test.com']);

        $user = User::where('email', 'operator.baru@test.com')->first();
        $this->assertTrue($user->hasRole('operator'));
    }

    #[Test]
    public function create_requires_name_email_and_password(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminUserForm::class)
            ->call('save')
            ->assertHasErrors(['name', 'email', 'password']);
    }

    #[Test]
    public function create_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'ada@test.com']);

        Livewire::actingAs($this->admin)
            ->test(AdminUserForm::class)
            ->set('name', 'Duplikat')
            ->set('email', 'ada@test.com')
            ->set('password', 'password123')
            ->set('role', 'operator')
            ->call('save')
            ->assertHasErrors(['email']);
    }

    // ── Edit ──────────────────────────────────────────────────────────────────

    #[Test]
    public function admin_can_edit_user_name_and_email(): void
    {
        $user = User::factory()->create(['name' => 'Lama', 'email' => 'lama@test.com']);
        $user->assignRole('operator');

        Livewire::actingAs($this->admin)
            ->test(AdminUserForm::class, ['user' => $user])
            ->set('name', 'Baru')
            ->set('email', 'baru@test.com')
            ->call('save');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Baru', 'email' => 'baru@test.com']);
    }

    #[Test]
    public function edit_password_is_optional(): void
    {
        $user = User::factory()->create();
        $user->assignRole('operator');
        $originalHash = $user->password;

        Livewire::actingAs($this->admin)
            ->test(AdminUserForm::class, ['user' => $user])
            ->set('name', $user->name)
            ->set('email', $user->email)
            ->set('password', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEquals($originalHash, $user->fresh()->password);
    }

    #[Test]
    public function admin_cannot_change_own_role(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminUserForm::class, ['user' => $this->admin])
            ->set('role', 'operator')
            ->call('save');

        $this->assertTrue($this->admin->fresh()->hasRole('admin'));
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    #[Test]
    public function admin_can_delete_another_user(): void
    {
        $user = User::factory()->create();
        $user->assignRole('operator');

        Livewire::actingAs($this->admin)
            ->test(AdminUserIndex::class)
            ->dispatch('delete-user', userId: $user->id);

        $this->assertModelMissing($user);
    }

    #[Test]
    public function admin_cannot_delete_self(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminUserIndex::class)
            ->dispatch('delete-user', userId: $this->admin->id)
            ->assertDispatched('toast');

        $this->assertModelExists($this->admin);
    }
}
