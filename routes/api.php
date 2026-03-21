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

    // Ricerca vendor basata su:
    // - città selezionata
    // - data dell'evento
    // - numero di invitati
    //
    // Il sistema restituisce:
    // - vendor disponibili per quella data
    // - raggruppati per categoria
    // - con i servizi offerti
    //
    // Se non esistono vendor disponibili nella città selezionata,
    // il sistema restituisce i vendor più vicini ordinati per distanza.
    Route::get('/vendors/search', [VendorSearchController::class, 'search']);

    // Recupera tutti i vendor per il catalogo, indipendentemente dalla disponibilità.
    Route::get('/vendors/catalog', [VendorCatalogController::class, 'index']);
    // Recupera i dettagli di un vendor specifico, inclusi servizi e disponibilità.
    Route::get('/vendors/{vendor}', [VendorCatalogController::class, 'show']);

    // Recupera la disponibilità degli slot per uno specifico vendor
    // in un intervallo di date.
    Route::get('/availability', [AvailabilityController::class, 'index']);

    // Blocca temporaneamente uno slot durante il checkout.
    Route::post('/slots/hold', [SlotController::class, 'hold']);

    // Conferma lo slot dopo pagamento accettato.
    Route::post('/slots/confirm', [SlotController::class, 'confirm']);

    // Libera lo slot in caso di annullamento o timeout.
    Route::post('/slots/release', [SlotController::class, 'release']);
});