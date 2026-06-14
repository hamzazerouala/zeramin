<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ShopController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\SellerController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\WishlistController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\PayoutController;
use App\Http\Controllers\Api\NotificationController;

/*
|--------------------------------------------------------------------------
| API Routes - Plateforme DropShop
|--------------------------------------------------------------------------
*/

// --- Authentification ---
Route::prefix('auth')->middleware('throttle:20,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/2fa/setup', [AuthController::class, 'setupTwoFactor']);
        Route::post('/2fa/verify', [AuthController::class, 'verifyTwoFactor']);
        Route::post('/2fa/disable', [AuthController::class, 'disableTwoFactor']);
    });
});

// --- Catalogue public ---
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/search', [ProductController::class, 'search']);
Route::get('/products/{product}', [ProductController::class, 'show']);
Route::get('/products/{product}/reviews', [ReviewController::class, 'index']);
Route::get('/shops/{slug}', [ShopController::class, 'show']);
Route::get('/shops/{slug}/products', [ProductController::class, 'byShop']);

// --- Panier ---
Route::prefix('cart')->group(function () {
    Route::get('/', [CartController::class, 'show']);
    Route::post('/items', [CartController::class, 'addItem']);
    Route::put('/items/{item}', [CartController::class, 'updateItem']);
    Route::delete('/items/{item}', [CartController::class, 'removeItem']);
    Route::post('/apply-coupon', [CartController::class, 'applyCoupon']);
    Route::delete('/coupon', [CartController::class, 'removeCoupon']);
});

// --- Checkout & paiement ---
Route::post('/checkout/calculate-shipping', [CheckoutController::class, 'calculateShipping']);
Route::post('/checkout/create-payment-intent', [CheckoutController::class, 'createPaymentIntent']);
Route::post('/payments/confirm', [PaymentController::class, 'confirm']);

// --- Webhooks ---
Route::post('/webhooks/stripe', [WebhookController::class, 'stripe']);

// --- Zone authentifiee ---
Route::middleware('auth:sanctum')->group(function () {

    // Profil & adresses
    Route::get('/user/profile', [UserController::class, 'profile']);
    Route::put('/user/profile', [UserController::class, 'updateProfile']);
    Route::get('/user/addresses', [UserController::class, 'addresses']);
    Route::post('/user/addresses', [UserController::class, 'storeAddress']);
    Route::put('/user/addresses/{address}', [UserController::class, 'updateAddress']);
    Route::delete('/user/addresses/{address}', [UserController::class, 'destroyAddress']);

    // Commandes client
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);

    // Avis
    Route::post('/products/{product}/reviews', [ReviewController::class, 'store']);

    // Wishlist
    Route::get('/wishlist', [WishlistController::class, 'index']);
    Route::post('/wishlist/items', [WishlistController::class, 'store']);
    Route::delete('/wishlist/items/{product}', [WishlistController::class, 'destroy']);

    // Support tickets
    Route::get('/tickets', [TicketController::class, 'index']);
    Route::post('/tickets', [TicketController::class, 'store']);
    Route::get('/tickets/{ticket}', [TicketController::class, 'show']);
    Route::post('/tickets/{ticket}/messages', [TicketController::class, 'addMessage']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);

    // --- Espace vendeur ---
    Route::prefix('seller')->middleware('seller')->group(function () {
        Route::get('/dashboard', [SellerController::class, 'dashboard']);
        Route::get('/analytics', [SellerController::class, 'analytics']);
        Route::get('/shop', [ShopController::class, 'mine']);
        Route::put('/shop', [ShopController::class, 'update']);

        Route::get('/products', [ProductController::class, 'sellerIndex']);
        Route::post('/products', [ProductController::class, 'store']);
        Route::post('/products/import-aliexpress', [ProductController::class, 'importFromAliExpress']);
        Route::get('/products/{product}', [ProductController::class, 'sellerShow']);
        Route::put('/products/{product}', [ProductController::class, 'update']);
        Route::delete('/products/{product}', [ProductController::class, 'destroy']);
        Route::get('/products/{product}/stats', [ProductController::class, 'stats']);

        Route::get('/orders', [SellerController::class, 'orders']);
        Route::get('/orders/{order}', [SellerController::class, 'orderShow']);
        Route::put('/orders/{order}/status', [SellerController::class, 'updateOrderStatus']);

        Route::get('/settings', [SellerController::class, 'settings']);
        Route::put('/settings', [SellerController::class, 'updateSettings']);

        // Virements
        Route::get('/payouts', [PayoutController::class, 'index']);
        Route::post('/payouts/request', [PayoutController::class, 'request']);

        // Stripe Connect
        Route::get('/stripe/onboard', [SellerController::class, 'stripeOnboard']);
        Route::get('/stripe/status', [SellerController::class, 'stripeStatus']);
    });

    // --- Espace admin ---
    Route::prefix('admin')->middleware('admin')->group(function () {
        Route::get('/users', [AdminController::class, 'users']);
        Route::put('/users/{user}/verify', [AdminController::class, 'verifyUser']);
        Route::get('/disputes', [AdminController::class, 'disputes']);
        Route::put('/disputes/{order}/resolve', [AdminController::class, 'resolveDispute']);
        Route::get('/tickets', [TicketController::class, 'adminIndex']);
        Route::put('/tickets/{ticket}/status', [TicketController::class, 'updateStatus']);
        Route::get('/stats', [AdminController::class, 'stats']);
    });
});
