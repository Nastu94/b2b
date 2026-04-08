<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Auth\MeController;
use App\Http\Controllers\Api\VendorSearchController;
use App\Http\Controllers\Api\AvailabilityController;
use App\Http\Controllers\Api\SlotController;
use App\Http\Controllers\Api\VendorCatalogController;

Route::middleware('auth:sanctum')->get('/user', MeController::class);

// Tutte le rotte sotto questo gruppo sono accessibili solo dal bridge
// PrestaShop tramite il middleware "booking.bridge".
Route::middleware('booking.bridge')->group(function () {

    // API in LETTURA (Throttle morbido: 120 rate/min)
    Route::middleware('throttle:120,1')->group(function () {
        // Ricerca vendor disponibili per data/ospiti/città
        Route::get('/vendors/search', [\App\Http\Controllers\Api\VendorSearchController::class, 'search']);
        Route::get('/event-types', [\App\Http\Controllers\Api\EventTypeController::class, 'index']);

        // Recupera tutti i vendor per il catalogo
        Route::get('/vendors/catalog', [VendorCatalogController::class, 'index']);

        // Recupera i dettagli vendor dal prodotto PrestaShop
        Route::get('/vendors/by-product/{idProduct}', [VendorCatalogController::class, 'showByProduct'])
            ->whereNumber('idProduct');

        // Recupera i dettagli di un vendor specifico (per ID o Slug retrocompatibile)
        Route::get('/vendors/{vendor?}', [VendorCatalogController::class, 'show']);

        // Recupera la disponibilità degli slot di un vendor
        Route::get('/availability', [AvailabilityController::class, 'index']);
    });

    // API in SCRITTURA / MUTAZIONE (Throttle severo anti-bot: 60 rate/min)
    Route::middleware('throttle:60,1')->group(function () {
        // Blocca temporaneamente uno slot (hold)
        Route::post('/slots/hold', [SlotController::class, 'hold']);

        // Conferma lo slot dopo pagamento
        Route::post('/slots/confirm', [SlotController::class, 'confirm']);

        // Libera lo slot manuale o timeout
        Route::post('/slots/release', [SlotController::class, 'release']);
    });
});