<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Livewire\Vendor\Dashboard\VendorDashboardPage;
use App\Livewire\Vendor\Offerings\ManageOfferings;
use App\Livewire\Vendor\Offerings\ManageOfferingsTabs;
use App\Livewire\Admin\Vendors\VendorCreatePage;
use App\Livewire\Admin\Dashboard\AdminDashboardPage;
use App\Livewire\Admin\Vendors\VendorEditPage;
use Illuminate\Support\Facades\Storage;


Route::get('/', function () {
    return view('welcome');
})->name('home');

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

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    // 'verified',
])->group(function () {

    Route::get('/dashboard', function () {
        $user = Auth::user();

        if ($user && $user->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        }

        if ($user && $user->hasRole('vendor')) {
            return redirect()->route('vendor.dashboard');
        }

        return view('dashboard');
    })->name('dashboard');

    // Vendor area
    Route::middleware(['role:vendor', 'permission:vendor.access', 'active.vendor'])
        ->prefix('vendor')
        ->name('vendor.')
        ->group(function () {
            Route::get('/dashboard', VendorDashboardPage::class)->name('dashboard');
            Route::get('/offerings', ManageOfferingsTabs::class)->name('offerings');
    });

    // Admin area
    Route::middleware(['role:admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', AdminDashboardPage::class)->name('dashboard');
        Route::get('/vendors/create', VendorCreatePage::class)->name('vendors.create');
        Route::get('/vendors/{vendorAccount}', VendorEditPage::class)->name('vendors.edit');
    });
});