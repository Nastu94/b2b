<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serve file pubblici dal disk "public" senza dipendere dal symlink /public/storage.
 * NOTA: qui NON applichiamo auth perché sono immagini pubbliche del catalogo.
 * Se vuoi restringere, metti middleware auth/role e/o firma URL.
 */
Route::get('/media/{path}', function (string $path): StreamedResponse {
    // Normalizza path ed evita traversal tipo ../
    $path = ltrim($path, '/');

    abort_unless(Storage::disk('public')->exists($path), 404);

    return Storage::disk('public')->response($path);
})->where('path', '.*')->name('media.public');