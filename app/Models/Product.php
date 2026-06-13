<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id', 'category_id', 'aliexpress_product_id', 'aliexpress_url', 'aliexpress_raw',
        'title', 'description', 'slug', 'cost_price', 'cost_currency', 'markup_coefficient',
        'markup_fixed', 'final_price_calculated', 'rating', 'rating_count', 'stock_aliexpress',
        'stock_platform', 'shipping_days_estimated', 'is_active', 'featured', 'meta_title',
        'meta_description', 'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'aliexpress_raw' => 'array',
            'cost_price' => 'decimal:2',
            'markup_coefficient' => 'decimal:2',
            'markup_fixed' => 'decimal:2',
            'final_price_calculated' => 'decimal:2',
            'rating' => 'decimal:2',
            'is_active' => 'boolean',
            'featured' => 'boolean',
            'synced_at' => 'datetime',
        ];
    }

    /**
     * Calcule le prix de vente : (cost_price × coefficient) + marge fixe.
     */
    public function computeFinalPrice(): float
    {
        return round(((float) $this->cost_price * (float) $this->markup_coefficient) + (float) $this->markup_fixed, 2);
    }

    /**
     * Accessor : prix affiché (valeur dénormalisée, fallback sur le calcul).
     */
    protected function displayPrice(): Attribute
    {
        return Attribute::make(
            get: fn () => (float) ($this->final_price_calculated ?: $this->computeFinalPrice()),
        );
    }

    public function isInStock(): bool
    {
        return $this->stock_platform > 0;
    }

    // --- Relations ---
    public function seller(): BelongsTo
    {
        return $this->belongsTo(SellerProfile::class, 'seller_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // --- Scopes ---
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }
}
