<?php

namespace Tests\Feature;

use App\Livewire\UiConfirmDialog;
use App\Livewire\UiToast;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UiComponentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'operator', 'guard_name' => 'web']);
    }

    // ── UiToast ────────────────────────────────────────────────────────────

    #[Test]
    public function toast_is_hidden_by_default(): void
    {
        Livewire::test(UiToast::class)
            ->assertSet('show', false);
    }

    #[Test]
    public function toast_shows_on_toast_event(): void
    {
        Livewire::test(UiToast::class)
            ->dispatch('toast', message: 'Data berhasil disimpan', type: 'success')
            ->assertSet('show', true)
            ->assertSet('message', 'Data berhasil disimpan')
            ->assertSet('type', 'success');
    }

    #[Test]
    public function toast_defaults_to_success_type(): void
    {
        Livewire::test(UiToast::class)
            ->dispatch('toast', message: 'Berhasil')
            ->assertSet('type', 'success');
    }

    #[Test]
    public function toast_can_show_error_type(): void
    {
        Livewire::test(UiToast::class)
            ->dispatch('toast', message: 'Terjadi kesalahan', type: 'error')
            ->assertSet('type', 'error')
            ->assertSet('show', true);
    }

    #[Test]
    public function toast_dismiss_hides_it(): void
    {
        Livewire::test(UiToast::class)
            ->dispatch('toast', message: 'Berhasil', type: 'success')
            ->assertSet('show', true)
            ->call('dismiss')
            ->assertSet('show', false);
    }

    // ── UiConfirmDialog ────────────────────────────────────────────────────

    #[Test]
    public function confirm_dialog_is_hidden_by_default(): void
    {
        Livewire::test(UiConfirmDialog::class)
            ->assertSet('show', false);
    }

    #[Test]
    public function confirm_dialog_opens_on_show_confirm_event(): void
    {
        Livewire::test(UiConfirmDialog::class)
            ->dispatch('show-confirm',
                message: 'Apakah anda yakin?',
                confirmedEvent: 'delete-record',
            )
            ->assertSet('show', true)
            ->assertSet('message', 'Apakah anda yakin?')
            ->assertSet('confirmedEvent', 'delete-record');
    }

    #[Test]
    public function confirm_dialog_uses_custom_labels(): void
    {
        Livewire::test(UiConfirmDialog::class)
            ->dispatch('show-confirm',
                message: 'Hapus data ini?',
                confirmedEvent: 'do-delete',
                confirmedData: [],
                confirmLabel: 'Hapus',
                cancelLabel: 'Tidak',
            )
            ->assertSet('confirmLabel', 'Hapus')
            ->assertSet('cancelLabel', 'Tidak');
    }

    #[Test]
    public function confirm_dialog_dispatches_confirmed_event_on_confirm(): void
    {
        Livewire::test(UiConfirmDialog::class)
            ->dispatch('show-confirm',
                message: 'Lanjutkan?',
                confirmedEvent: 'record-deleted',
            )
            ->call('confirm')
            ->assertSet('show', false)
            ->assertDispatched('record-deleted');
    }

    #[Test]
    public function confirm_dialog_cancel_hides_without_dispatching(): void
    {
        Livewire::test(UiConfirmDialog::class)
            ->dispatch('show-confirm',
                message: 'Lanjutkan?',
                confirmedEvent: 'record-deleted',
            )
            ->call('cancel')
            ->assertSet('show', false)
            ->assertNotDispatched('record-deleted');
    }

    #[Test]
    public function confirm_dialog_passes_data_with_confirmed_event(): void
    {
        Livewire::test(UiConfirmDialog::class)
            ->dispatch('show-confirm',
                message: 'Hapus?',
                confirmedEvent: 'item-confirmed',
                confirmedData: [42],
            )
            ->call('confirm')
            ->assertDispatched('item-confirmed');
    }

    // ── Layout Integration ─────────────────────────────────────────────────

    #[Test]
    public function admin_layout_renders_global_ui_components(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSeeLivewire(UiToast::class)
            ->assertSeeLivewire(UiConfirmDialog::class);
    }
}
