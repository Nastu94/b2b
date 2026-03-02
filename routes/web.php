<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

// Livewire pages
use App\Livewire\Vendor\Dashboard\VendorDashboardPage;
use App\Livewire\Vendor\Offerings\ManageOfferingsTabs;
use App\Livewire\Vendor\Profile\VendorProfilePage;
use App\Livewire\Admin\Dashboard\AdminDashboardPage;
use App\Livewire\Admin\Vendors\VendorCreatePage;
use App\Livewire\Admin\Vendors\VendorProfileTabs;

// Test / Dev
use App\Models\Offering;
use App\Services\GeocodingService;
use App\Services\VendorSearchService;

Route::get('/', function () {
    return view('welcome');
})->name('home');

/**
 * Serve file pubblici dal disk "public" senza dipendere dal symlink /public/storage.
 * NOTA: qui NON applichiamo auth perché sono immagini pubbliche del catalogo.
 * Se vuoi restringere, metti middleware auth/role e/o firma URL.
 */
Route::get('/media/{path}', function (string $path): StreamedResponse {
    $path = ltrim($path, '/');
    abort_unless(Storage::disk('public')->exists($path), 404);

    return Storage::disk('public')->response($path);
})->where('path', '.*')->name('media.public');


/**
 * TEST "CLIENTE" (solo local/dev)
 * Pagina per simulare: data + slot_slug + offering + indirizzo -> lista vendor vicini disponibili
 */
if (app()->environment('local')) {

    Route::match(['get', 'post'], '/test/vendor-search', function (
        Request $request,
        GeocodingService $geo,
        VendorSearchService $search
    ) {
        // Dati per UI
        $categories = \App\Models\Category::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        $selectedCategoryId = $request->input('category_id');

        $offeringsQuery = \App\Models\Offering::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($selectedCategoryId) {
            $offeringsQuery->where('category_id', (int)$selectedCategoryId);
        }

        $offerings = $offeringsQuery->get(['id', 'name', 'category_id']);

        // Slot disponibili: prendiamo slug/label distinti (catalogo “logico” di fatto)
        $slots = \App\Models\VendorSlot::query()
            ->where('is_active', true)
            ->select('slug', 'label', 'start_time', 'end_time')
            ->distinct()
            ->orderBy('label')
            ->orderBy('start_time')
            ->get();

        // Stato view (default)
        $viewData = [
            'categories' => $categories,
            'offerings' => $offerings,
            'slots' => $slots,
            'results' => [],
            'coords' => null,
            'error' => null,
            'old' => $request->all(),
        ];

        // GET = solo render pagina
        if ($request->isMethod('get')) {
            return view('test.vendor-search', $viewData);
        }

        // POST = valida + geocoding + search
        $data = $request->validate([
            'category_id' => 'nullable|integer|exists:categories,id',
            'offering_id' => 'required|integer|exists:offerings,id',
            'date' => 'required|date_format:Y-m-d',
            'slot_slug' => 'required|string|max:80',
            'radius_km' => 'nullable|numeric|min:1|max:200',

            'address_line1' => 'required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'postal_code' => 'required|string|max:20',
            'city' => 'required|string|max:120',
            'region' => 'nullable|string|max:120',
        ]);

        $coords = $geo->geocodeItaly($data);

        if (!$coords) {
            $viewData['error'] = 'Indirizzo non trovato.';
            return view('test.vendor-search', $viewData);
        }

        $results = $search->search([
            'offering_id' => (int) $data['offering_id'],
            'date' => $data['date'],
            'slot_slug' => $data['slot_slug'],
            'lat' => (float) $coords['lat'],
            'lng' => (float) $coords['lng'],
            'radius_km' => (float) ($data['radius_km'] ?? 30),
            'limit' => 50,
        ]);

        $viewData['coords'] = $coords;
        $viewData['results'] = $results;

        return view('test.vendor-search', $viewData);
    })->name('test.vendor-search');
}


/**
 * Area autenticata
 */
Route::middleware([
    'auth',
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
            Route::get('/profile', VendorProfilePage::class)->name('profile');
        });

    // Admin area
    Route::middleware(['role:admin'])
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::get('/dashboard', AdminDashboardPage::class)->name('dashboard');
            Route::get('/vendors/create', VendorCreatePage::class)->name('vendors.create');
            Route::get('/vendors/{vendorAccount}', VendorProfileTabs::class)->name('vendors.edit');
        });
});
