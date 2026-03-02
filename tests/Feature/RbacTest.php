<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'operator', 'guard_name' => 'web']);
    }

    #[Test]
    public function unauthenticated_user_is_redirected_to_login(): void
    {
        $this->get('/admin/dashboard')->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_access_admin_dashboard(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get('/admin/dashboard')
            ->assertOk();
    }

    #[Test]
    public function operator_cannot_access_admin_dashboard(): void
    {
        $operator = User::factory()->create();
        $operator->assignRole('operator');

        $this->actingAs($operator)
            ->get('/admin/dashboard')
            ->assertStatus(403);
    }

    #[Test]
    public function user_without_role_cannot_access_admin_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/admin/dashboard')
            ->assertStatus(403);
    }

    #[Test]
    public function operator_can_access_ops_routes(): void
    {
        $operator = User::factory()->create();
        $operator->assignRole('operator');

        // Stub routes return 404 until Phase 8 — verify middleware passes (not 302/403)
        $response = $this->actingAs($operator)->get('/ops/events/1/scan');
        $this->assertNotEquals(403, $response->status());
        $this->assertNotEquals(302, $response->status());
    }

    #[Test]
    public function admin_cannot_access_ops_routes(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $event = Event::factory()->create();

        $this->actingAs($admin)
            ->get(route('ops.events.scan', $event))
            ->assertForbidden();
    }

    #[Test]
    public function login_redirects_admin_to_admin_dashboard(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
        ]);
        $admin->assignRole('admin');

        $this->post(route('login'), [
            'email' => 'admin@test.com',
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));
    }
}
