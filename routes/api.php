<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\MeController;

/**
 * Route API protetta da Sanctum.
 *
 * Nota:
 * - Manteniamo URI e middleware identici.
 * - Spostiamo la logica in un Controller per poterla documentare bene con Scribe.
 */
Route::middleware('auth:sanctum')->get('/user', MeController::class);