<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\MeController;
use App\Http\Controllers\Api\VendorSearchController;
use App\Http\Controllers\Api\AvailabilityController;
use App\Http\Controllers\Api\SlotController;

/**
 * Route API protetta da Sanctum.
 *
 * Nota:
 * - Manteniamo URI e middleware identici.
 * - Spostiamo la logica in un Controller per poterla documentare bene con Scribe.
 */
Route::middleware('auth:sanctum')->get('/user', MeController::class);

// Vendor search (per PrestaShop)
Route::get('/vendors/search', [VendorSearchController::class, 'search']);

// Disponibilità calendario
Route::get('/availability', [AvailabilityController::class, 'index']);

// Gestione slot (hold/confirm/release)
Route::post('/slots/hold', [SlotController::class, 'hold']);
Route::post('/slots/confirm', [SlotController::class, 'confirm']);
Route::post('/slots/release', [SlotController::class, 'release']);