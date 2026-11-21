<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Promo extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'subtitle',
        'badge_text',
        'image',
        'action_type',
        'package_id',
        'link_url',
        'order',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    protected $appends = [
        'image_url',
    ];

    public function package(): BelongsTo
    {
        return $this->belongsTo(InternetPackage::class);
    }

    public function getImageUrlAttribute(): string
    {
        if (empty($this->image)) {
            return '';
        }

        if (str_starts_with($this->image, 'http://') || str_starts_with($this->image, 'https://')) {
            return $this->image;
        }

        return url(Storage::disk('public')->url($this->image));
    }
}
