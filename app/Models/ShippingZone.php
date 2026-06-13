<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id', 'country', 'type', 'cost',
        'is_free_above', 'free_above_amount', 'delivery_days',
    ];

    protected function casts(): array
    {
        return [
            'cost' => 'decimal:2',
            'free_above_amount' => 'decimal:2',
            'is_free_above' => 'boolean',
        ];
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(SellerProfile::class, 'seller_id');
    }
}
