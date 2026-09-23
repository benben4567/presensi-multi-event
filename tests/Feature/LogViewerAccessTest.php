<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LogViewerAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'operator', 'guard_name' => 'web']);
    }

    #[Test]
    public function admin_is_allowed_to_view_log_viewer(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->assertTrue(Gate::forUser($admin)->allows('viewLogViewer'));
    }

    #[Test]
    public function operator_is_not_allowed_to_view_log_viewer(): void
    {
        $operator = User::factory()->create();
        $operator->assignRole('operator');

        $this->assertFalse(Gate::forUser($operator)->allows('viewLogViewer'));
    }
}
