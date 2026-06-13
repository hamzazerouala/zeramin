<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number', 'seller_id', 'customer_id', 'email_guest', 'subtotal',
        'shipping_cost', 'tax_amount', 'total_amount', 'currency', 'status',
        'payment_intent_id', 'payment_method', 'payment_status', 'shipping_address',
        'billing_address', 'shipping_method', 'aliexpress_order_number',
        'aliexpress_tracking_id', 'tracking_url', 'estimated_delivery_date',
        'delivered_at', 'notes_internal',
    ];

    protected function casts(): array
    {
        return [
            'shipping_address' => 'array',
            'billing_address' => 'array',
            'subtotal' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'estimated_delivery_date' => 'date',
            'delivered_at' => 'datetime',
        ];
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(SellerProfile::class, 'seller_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function paymentIntent(): HasOne
    {
        return $this->hasOne(PaymentIntent::class);
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class, 'seller_id', 'seller_id');
    }

    /**
     * Génère un numéro de commande unique (ex: FRA20260613A1B2C3).
     */
    public static function generateOrderNumber(string $countryPrefix = 'FRA'): string
    {
        return strtoupper($countryPrefix)
            .now()->format('Ymd')
            .strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
    }
}
