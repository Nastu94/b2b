<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\EventType;
use App\Models\Offering;
use App\Models\User;
use App\Models\VendorAccount;
use App\Models\VendorOfferingProfile;
use App\Services\AvailabilityService;
use App\Services\GeocodingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class VendorSearchFiltersTest extends TestCase
{
    use RefreshDatabase;
    use WithoutMiddleware;

    public function test_all_search_filters_are_preserved_and_applied_to_the_same_profile(): void
    {
        Queue::fake();
        config(['booking_bridge.geocoding_fallback_mode' => 'strict']);

        $category = Category::create([
            'name' => 'Intrattenimento',
            'slug' => 'intrattenimento',
            'is_active' => true,
            'prestashop_category_id' => 900,
        ]);
        $mobileOffering = Offering::create([
            'category_id' => $category->id,
            'name' => 'DJ mobile',
            'slug' => 'dj-mobile',
            'is_active' => true,
        ]);
        $fixedOffering = Offering::create([
            'category_id' => $category->id,
            'name' => 'Sala fissa',
            'slug' => 'sala-fissa',
            'is_active' => true,
        ]);
        $eventType = EventType::create(['name' => 'Matrimonio', 'is_active' => true]);
        $vendor = VendorAccount::create([
            'user_id' => User::factory()->create()->id,
            'category_id' => $category->id,
            'company_name' => 'Vendor filtri',
            'status' => 'ACTIVE',
            'legal_city' => 'Milano',
        ]);

        $vendor->offerings()->attach($mobileOffering->id, ['is_active' => true]);
        $vendor->offerings()->attach($fixedOffering->id, ['is_active' => true]);
        $vendor->eventTypes()->attach($eventType->id);

        VendorOfferingProfile::create([
            'vendor_account_id' => $vendor->id,
            'offering_id' => $mobileOffering->id,
            'title' => 'Servizio mobile',
            'service_mode' => 'MOBILE',
            'max_guests' => 100,
            'is_published' => true,
            'is_approved' => true,
        ]);
        VendorOfferingProfile::create([
            'vendor_account_id' => $vendor->id,
            'offering_id' => $fixedOffering->id,
            'title' => 'Servizio fisso',
            'service_mode' => 'FIXED_LOCATION',
            'max_guests' => 100,
            'is_published' => true,
            'is_approved' => true,
        ]);

        $this->mock(GeocodingService::class, function ($mock) {
            $mock->shouldReceive('geocodeCity')
                ->with('Milano', 'Lombardia')
                ->twice()
                ->andReturn(null);
        });
        $this->mock(AvailabilityService::class, function ($mock) {
            $mock->shouldReceive('getAvailability')
                ->once()
                ->andReturnUsing(fn ($vendorAccountId, $from) => [
                    $from => [['status' => 'AVAILABLE']],
                ]);
        });

        $date = now()->addDays(10)->format('Y-m-d');
        $query = http_build_query([
            'city' => 'Milano',
            'region' => 'Lombardia',
            'date' => $date,
            'guests' => 50,
            'prestashop_category_id' => 900,
            'category_id' => $category->id,
            'event_type_id' => $eventType->id,
            'offering_id' => $mobileOffering->id,
            'service_mode' => 'mobile',
        ]);

        $this->getJson('/api/vendors/search?' . $query)
            ->assertOk()
            ->assertJsonPath('region', 'Lombardia')
            ->assertJsonPath('filters.guests', 50)
            ->assertJsonPath('filters.prestashop_category_id', 900)
            ->assertJsonPath('filters.category_id', $category->id)
            ->assertJsonPath('filters.event_type_id', $eventType->id)
            ->assertJsonPath('filters.offering_id', $mobileOffering->id)
            ->assertJsonPath('filters.service_mode', 'MOBILE')
            ->assertJsonPath('total', 1)
            ->assertJsonCount(1, 'data.0.vendors.0.offerings')
            ->assertJsonPath('data.0.vendors.0.offerings.0.offering_id', $mobileOffering->id);

        $mismatchedQuery = http_build_query([
            'city' => 'Milano',
            'region' => 'Lombardia',
            'date' => $date,
            'offering_id' => $fixedOffering->id,
            'service_mode' => 'MOBILE',
        ]);

        $this->getJson('/api/vendors/search?' . $mismatchedQuery)
            ->assertOk()
            ->assertJsonPath('total', 0);
    }
}
