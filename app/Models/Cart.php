<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id', 'session_id', 'payment_intent_id', 'shipping_address',
        'coupon_code', 'total_amount', 'currency', 'expired_at',
    ];

    protected function casts(): array
    {
        return [
            'shipping_address' => 'array',
            'total_amount' => 'decimal:2',
            'expired_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function subtotal(): float
    {
        return (float) $this->items
            ->sum(fn (CartItem $i) => $i->product->display_price * $i->quantity);
    }
}
