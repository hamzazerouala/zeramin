<?php

use App\Http\Controllers\Web\SpaController;
use Illuminate\Support\Facades\Route;

// Toutes les routes non-API renvoient le SPA React (fallback).
Route::get('/{any?}', [SpaController::class, 'index'])
    ->where('any', '^(?!api).*$');
