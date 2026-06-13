<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SellerProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'shop_slug', 'shop_name', 'logo_url', 'banner_url', 'bio',
        'contact_email', 'country', 'stripe_connect_id', 'aliexpress_account',
        'aliexpress_password_encrypted', 'kyc_verified_at', 'commission_rate',
        'avg_rating', 'total_sales', 'is_active',
    ];

    protected $hidden = ['aliexpress_password_encrypted'];

    protected function casts(): array
    {
        return [
            'kyc_verified_at' => 'datetime',
            'commission_rate' => 'decimal:2',
            'avg_rating' => 'decimal:2',
            'is_active' => 'boolean',
            // Chiffrement transparent du mot de passe AliExpress.
            'aliexpress_password_encrypted' => 'encrypted',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'seller_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'seller_id');
    }

    public function shippingZones(): HasMany
    {
        return $this->hasMany(ShippingZone::class, 'seller_id');
    }

    public function promoCodes(): HasMany
    {
        return $this->hasMany(PromoCode::class, 'seller_id');
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class, 'seller_id');
    }
}
