<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function guest_hitting_unknown_url_sees_custom_404_page(): void
    {
        $this->get('/this-route-does-not-exist')
            ->assertNotFound()
            ->assertSee('Halaman Tidak Ditemukan');
    }

    #[Test]
    public function operator_hitting_admin_route_sees_custom_403_page(): void
    {
        Role::firstOrCreate(['name' => 'operator', 'guard_name' => 'web']);
        $operator = User::factory()->create();
        $operator->assignRole('operator');

        $this->actingAs($operator)
            ->get(route('admin.users.index'))
            ->assertForbidden()
            ->assertSee('Akses Ditolak');
    }

    #[Test]
    public function all_custom_error_views_render_without_error(): void
    {
        $expectedTitles = [
            401 => 'Perlu Masuk Terlebih Dahulu',
            403 => 'Akses Ditolak',
            404 => 'Halaman Tidak Ditemukan',
            419 => 'Sesi Kedaluwarsa',
            429 => 'Terlalu Banyak Permintaan',
            500 => 'Terjadi Kesalahan Server',
            503 => 'Sedang Pemeliharaan',
        ];

        foreach ($expectedTitles as $code => $title) {
            $html = view('errors.'.$code)->render();

            $this->assertStringContainsString($title, $html);
            $this->assertStringContainsString((string) $code, $html);
        }
    }

    #[Test]
    public function maintenance_page_has_no_home_link(): void
    {
        $html = view('errors.503')->render();

        $this->assertStringNotContainsString('Kembali ke Beranda', $html);
        $this->assertStringContainsString('Muat Ulang Halaman', $html);
    }
}
