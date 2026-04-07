<?php

namespace App\Livewire;

use App\Models\PrintTemplate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.admin')]
#[Title('Template Cetak')]
class AdminPrintTemplateForm extends Component
{
    use WithFileUploads;

    public ?int $templateId = null;

    public string $name = '';

    public int $pageWidthMm = 80;

    public int $pageHeightMm = 105;

    public string $existingImagePath = '';

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $photo = null;

    public float $qrXMm = 20.0;

    public float $qrYMm = 30.0;

    public float $qrWMm = 40.0;

    public float $qrHMm = 40.0;

    public function mount(?PrintTemplate $template = null): void
    {
        if ($template && $template->exists) {
            $this->templateId = $template->id;
            $this->name = $template->name;
            $this->pageWidthMm = $template->page_width_mm;
            $this->pageHeightMm = $template->page_height_mm;
            $this->existingImagePath = $template->background_image_path;
            $this->qrXMm = $template->qr_x_mm;
            $this->qrYMm = $template->qr_y_mm;
            $this->qrWMm = $template->qr_w_mm;
            $this->qrHMm = $template->qr_h_mm;
        }
    }

    public function save(): void
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'pageWidthMm' => ['required', 'integer', 'min:50', 'max:300'],
            'pageHeightMm' => ['required', 'integer', 'min:50', 'max:400'],
            'qrXMm' => ['required', 'numeric', 'min:0'],
            'qrYMm' => ['required', 'numeric', 'min:0'],
            'qrWMm' => ['required', 'numeric', 'min:30'],
            'qrHMm' => ['required', 'numeric', 'min:30'],
        ];

        if ($this->templateId === null) {
            $rules['photo'] = ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'];
        } else {
            $rules['photo'] = ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'];
        }

        $this->validate($rules, [
            'name.required' => 'Nama template wajib diisi.',
            'photo.required' => 'Gambar latar wajib diunggah.',
            'photo.image' => 'File harus berupa gambar.',
            'photo.mimes' => 'Format gambar harus JPG atau PNG.',
            'photo.max' => 'Ukuran gambar maksimal 5 MB.',
            'qrWMm.min' => 'Lebar area QR minimal 30 mm.',
            'qrHMm.min' => 'Tinggi area QR minimal 30 mm.',
            'qrXMm.min' => 'Posisi X area QR tidak boleh negatif.',
            'qrYMm.min' => 'Posisi Y area QR tidak boleh negatif.',
        ]);

        // Validate QR area stays within page bounds
        if ($this->qrXMm + $this->qrWMm > $this->pageWidthMm) {
            $this->addError('qrWMm', 'Area QR melampaui batas kanan halaman.');

            return;
        }

        if ($this->qrYMm + $this->qrHMm > $this->pageHeightMm) {
            $this->addError('qrHMm', 'Area QR melampaui batas bawah halaman.');

            return;
        }

        $imagePath = $this->existingImagePath;

        if ($this->photo) {
            // Delete old image if replacing
            if ($this->templateId && $imagePath) {
                Storage::disk('public')->delete($imagePath);
            }

            $imagePath = $this->photo->store('print-templates', 'public');
        }

        $data = [
            'name' => $this->name,
            'page_width_mm' => $this->pageWidthMm,
            'page_height_mm' => $this->pageHeightMm,
            'background_image_path' => $imagePath,
            'qr_x_mm' => $this->qrXMm,
            'qr_y_mm' => $this->qrYMm,
            'qr_w_mm' => $this->qrWMm,
            'qr_h_mm' => $this->qrHMm,
        ];

        if ($this->templateId) {
            PrintTemplate::findOrFail($this->templateId)->update($data);
        } else {
            $data['created_by'] = Auth::id();
            PrintTemplate::create($data);
        }

        $this->dispatch('toast',
            message: $this->templateId ? 'Template berhasil diperbarui.' : 'Template berhasil dibuat.',
            type: 'success',
        );

        $this->redirect(route('admin.print-templates.index'), navigate: true);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin-print-template-form');
    }
}
