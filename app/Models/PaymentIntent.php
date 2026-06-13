<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentIntent extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'stripe_payment_intent_id', 'stripe_client_secret', 'amount',
        'currency', 'status', 'error_message', 'payment_method_used', 'charge_id',
    ];

    protected $hidden = ['stripe_client_secret'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
