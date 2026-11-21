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
        'content',
        'offer_title',
        'offer_duration',
        'original_price',
        'promo_price',
        'discount_label',
        'price_unit',
        'cta_text',
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
        'original_price' => 'float',
        'promo_price' => 'float',
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

    public function getFormattedDiscountLabelAttribute(): ?string
    {
        if (! empty($this->discount_label)) {
            return $this->discount_label;
        }

        if ($this->original_price && $this->promo_price && $this->original_price > $this->promo_price) {
            $pct = round((($this->original_price - $this->promo_price) / $this->original_price) * 100);

            return "-{$pct}%";
        }

        return null;
    }
}
