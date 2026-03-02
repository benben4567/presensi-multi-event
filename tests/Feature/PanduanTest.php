<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PanduanTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $operator;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'operator', 'guard_name' => 'web']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->operator = User::factory()->create();
        $this->operator->assignRole('operator');
    }

    // ── Admin panduan ─────────────────────────────────────────────────────────

    #[Test]
    public function guest_cannot_access_admin_panduan(): void
    {
        $this->get(route('admin.panduan'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function operator_cannot_access_admin_panduan(): void
    {
        $this->actingAs($this->operator)
            ->get(route('admin.panduan'))
            ->assertForbidden();
    }

    #[Test]
    public function admin_can_access_panduan(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.panduan'))
            ->assertOk()
            ->assertSee('Panduan')
            ->assertSee('Mulai Cepat')
            ->assertSee('FAQ');
    }

    // ── Operator panduan ──────────────────────────────────────────────────────

    #[Test]
    public function guest_cannot_access_ops_panduan(): void
    {
        $this->get(route('ops.panduan'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_cannot_access_ops_panduan(): void
    {
        $this->actingAs($this->admin)
            ->get(route('ops.panduan'))
            ->assertForbidden();
    }

    #[Test]
    public function operator_can_access_panduan(): void
    {
        $this->actingAs($this->operator)
            ->get(route('ops.panduan'))
            ->assertOk()
            ->assertSee('Panduan')
            ->assertSee('Mulai Cepat')
            ->assertSee('FAQ');
    }
}
