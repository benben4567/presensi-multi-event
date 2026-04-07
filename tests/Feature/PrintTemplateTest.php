<?php

namespace Tests\Feature;

use App\Livewire\AdminPrintTemplateForm;
use App\Livewire\AdminPrintTemplateIndex;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Invitation;
use App\Models\PrintTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PrintTemplateTest extends TestCase
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

    // ── Access control ────────────────────────────────────────────────────────

    #[Test]
    public function guest_cannot_access_template_index(): void
    {
        $this->get(route('admin.print-templates.index'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function operator_cannot_access_template_index(): void
    {
        $this->actingAs($this->operator)
            ->get(route('admin.print-templates.index'))
            ->assertForbidden();
    }

    #[Test]
    public function admin_can_access_template_index(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.print-templates.index'))
            ->assertOk();
    }

    #[Test]
    public function admin_can_access_template_create_form(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.print-templates.create'))
            ->assertOk();
    }

    // ── Create template ───────────────────────────────────────────────────────

    #[Test]
    public function admin_can_create_template_with_valid_data(): void
    {
        Storage::fake('public');

        $image = UploadedFile::fake()->image('bg.jpg', 800, 1050);

        Livewire::actingAs($this->admin)
            ->test(AdminPrintTemplateForm::class)
            ->set('name', 'Template SEMNAS 2026')
            ->set('pageWidthMm', 80)
            ->set('pageHeightMm', 105)
            ->set('qrXMm', 20)
            ->set('qrYMm', 30)
            ->set('qrWMm', 40)
            ->set('qrHMm', 40)
            ->set('photo', $image)
            ->call('save')
            ->assertDispatched('toast');

        $this->assertDatabaseHas('print_templates', [
            'name' => 'Template SEMNAS 2026',
            'page_width_mm' => 80,
            'page_height_mm' => 105,
            'qr_x_mm' => 20,
            'qr_y_mm' => 30,
            'qr_w_mm' => 40,
            'qr_h_mm' => 40,
            'is_active' => true,
        ]);
    }

    // ── Validation ────────────────────────────────────────────────────────────

    #[Test]
    public function validation_fails_when_name_is_missing(): void
    {
        Storage::fake('public');

        Livewire::actingAs($this->admin)
            ->test(AdminPrintTemplateForm::class)
            ->set('name', '')
            ->set('qrXMm', 20)->set('qrYMm', 30)->set('qrWMm', 40)->set('qrHMm', 40)
            ->set('photo', UploadedFile::fake()->image('bg.jpg'))
            ->call('save')
            ->assertHasErrors(['name']);
    }

    #[Test]
    public function validation_fails_when_photo_is_missing_on_create(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminPrintTemplateForm::class)
            ->set('name', 'Test')
            ->set('qrXMm', 20)->set('qrYMm', 30)->set('qrWMm', 40)->set('qrHMm', 40)
            ->call('save')
            ->assertHasErrors(['photo']);
    }

    #[Test]
    public function validation_fails_when_qr_width_is_below_30mm(): void
    {
        Storage::fake('public');

        Livewire::actingAs($this->admin)
            ->test(AdminPrintTemplateForm::class)
            ->set('name', 'Test')
            ->set('qrXMm', 0)->set('qrYMm', 0)->set('qrWMm', 20)->set('qrHMm', 40)
            ->set('photo', UploadedFile::fake()->image('bg.png'))
            ->call('save')
            ->assertHasErrors(['qrWMm']);
    }

    #[Test]
    public function validation_fails_when_qr_height_is_below_30mm(): void
    {
        Storage::fake('public');

        Livewire::actingAs($this->admin)
            ->test(AdminPrintTemplateForm::class)
            ->set('name', 'Test')
            ->set('qrXMm', 0)->set('qrYMm', 0)->set('qrWMm', 40)->set('qrHMm', 20)
            ->set('photo', UploadedFile::fake()->image('bg.png'))
            ->call('save')
            ->assertHasErrors(['qrHMm']);
    }

    #[Test]
    public function validation_fails_when_qr_area_exceeds_page_width(): void
    {
        Storage::fake('public');

        Livewire::actingAs($this->admin)
            ->test(AdminPrintTemplateForm::class)
            ->set('name', 'Test')
            ->set('pageWidthMm', 80)
            ->set('pageHeightMm', 105)
            ->set('qrXMm', 50)->set('qrYMm', 10)->set('qrWMm', 40)->set('qrHMm', 40)
            ->set('photo', UploadedFile::fake()->image('bg.png'))
            ->call('save')
            ->assertHasErrors(['qrWMm']);
    }

    #[Test]
    public function validation_fails_when_qr_area_exceeds_page_height(): void
    {
        Storage::fake('public');

        Livewire::actingAs($this->admin)
            ->test(AdminPrintTemplateForm::class)
            ->set('name', 'Test')
            ->set('pageWidthMm', 80)
            ->set('pageHeightMm', 105)
            ->set('qrXMm', 10)->set('qrYMm', 70)->set('qrWMm', 40)->set('qrHMm', 40)
            ->set('photo', UploadedFile::fake()->image('bg.png'))
            ->call('save')
            ->assertHasErrors(['qrHMm']);
    }

    // ── Toggle active ─────────────────────────────────────────────────────────

    #[Test]
    public function admin_can_deactivate_a_template(): void
    {
        $template = PrintTemplate::factory()->create(['is_active' => true]);

        Livewire::actingAs($this->admin)
            ->test(AdminPrintTemplateIndex::class)
            ->call('toggleActive', $template->id)
            ->assertDispatched('toast');

        $this->assertFalse($template->fresh()->is_active);
    }

    #[Test]
    public function admin_can_reactivate_a_template(): void
    {
        $template = PrintTemplate::factory()->create(['is_active' => false]);

        Livewire::actingAs($this->admin)
            ->test(AdminPrintTemplateIndex::class)
            ->call('toggleActive', $template->id)
            ->assertDispatched('toast');

        $this->assertTrue($template->fresh()->is_active);
    }

    // ── Event form — template selection ──────────────────────────────────────

    #[Test]
    public function event_can_be_saved_with_selected_template(): void
    {
        $template = PrintTemplate::factory()->create(['is_active' => true]);

        $event = Event::factory()->create([
            'settings' => ['enable_checkout' => false, 'operator_display_fields' => ['name']],
        ]);

        Livewire::actingAs($this->admin)
            ->test(\App\Livewire\AdminEventForm::class, ['event' => $event])
            ->set('printTemplateId', (string) $template->id)
            ->call('save');

        $this->assertSame($template->id, $event->fresh()->settings['print_template_id']);
    }

    #[Test]
    public function event_template_can_be_cleared(): void
    {
        $template = PrintTemplate::factory()->create(['is_active' => true]);

        $event = Event::factory()->create([
            'settings' => [
                'enable_checkout' => false,
                'operator_display_fields' => ['name'],
                'print_template_id' => $template->id,
            ],
        ]);

        Livewire::actingAs($this->admin)
            ->test(\App\Livewire\AdminEventForm::class, ['event' => $event])
            ->set('printTemplateId', '')
            ->call('save');

        $this->assertNull($event->fresh()->settings['print_template_id']);
    }

    // ── Individual print — template PDF path ─────────────────────────────────

    #[Test]
    public function individual_print_returns_pdf_when_event_has_template(): void
    {
        Storage::fake('public');

        $image = UploadedFile::fake()->image('bg.jpg', 800, 1050);
        $imagePath = $image->store('print-templates', 'public');

        $template = PrintTemplate::factory()->create([
            'background_image_path' => $imagePath,
            'page_width_mm' => 80,
            'page_height_mm' => 105,
            'qr_x_mm' => 20,
            'qr_y_mm' => 30,
            'qr_w_mm' => 40,
            'qr_h_mm' => 40,
        ]);

        $event = Event::factory()->create([
            'settings' => ['print_template_id' => $template->id],
        ]);

        $ep = EventParticipant::factory()->for($event)->create();

        Invitation::factory()->for($ep, 'eventParticipant')->create([
            'token' => Str::random(32),
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.events.participants.card', [$event, $ep]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    #[Test]
    public function individual_print_falls_back_to_html_when_no_template(): void
    {
        $event = Event::factory()->create([
            'settings' => ['print_template_id' => null],
        ]);

        $ep = EventParticipant::factory()->for($event)->create();

        Invitation::factory()->for($ep, 'eventParticipant')->create([
            'token' => Str::random(32),
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.events.participants.card', [$event, $ep]))
            ->assertOk()
            ->assertHeader('content-type', 'text/html; charset=UTF-8');
    }
}
