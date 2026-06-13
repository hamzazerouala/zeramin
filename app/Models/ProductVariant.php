<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id', 'aliexpress_sku_id', 'variant_name', 'variant_values',
        'cost_price_variant', 'markup_coefficient', 'markup_fixed', 'stock_aliexpress_variant',
    ];

    protected function casts(): array
    {
        return [
            'variant_values' => 'array',
            'cost_price_variant' => 'decimal:2',
            'markup_coefficient' => 'decimal:2',
            'markup_fixed' => 'decimal:2',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
