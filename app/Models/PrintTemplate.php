<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrintTemplate extends Model
{
    /** @use HasFactory<\Database\Factories\PrintTemplateFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'page_width_mm',
        'page_height_mm',
        'background_image_path',
        'qr_x_mm',
        'qr_y_mm',
        'qr_w_mm',
        'qr_h_mm',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'page_width_mm' => 'integer',
            'page_height_mm' => 'integer',
            'qr_x_mm' => 'float',
            'qr_y_mm' => 'float',
            'qr_w_mm' => 'float',
            'qr_h_mm' => 'float',
            'is_active' => 'boolean',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
