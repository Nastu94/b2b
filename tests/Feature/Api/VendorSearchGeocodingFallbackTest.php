<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Offering;
use App\Models\User;
use App\Models\VendorAccount;
use App\Models\VendorOfferingProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class VendorSearchGeocodingFallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_strict_mode_excludes_invalid_vendors()
    {
        Queue::fake();
        // Create dependencies
        $user = new User();
        $user->name = 'Test User';
        $user->email = 'test' . uniqid() . '@example.com';
        $user->password = bcrypt('password');
        $user->save();

        $category = Category::create(['name' => 'Cat1', 'slug' => 'cat1-' . uniqid(), 'is_active' => true]);
        $offering = Offering::create(['name' => 'Off1', 'slug' => 'off1-' . uniqid(), 'category_id' => $category->id, 'is_active' => true]);

        // Vendor 1: Milano (matches search city)
        $vendor1 = VendorAccount::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'company_name' => 'V1',
            'status' => 'ACTIVE',
            'legal_city' => 'Milano'
        ]);
        $vendor1->offerings()->attach($offering->id, ['is_active' => true]);
        VendorOfferingProfile::create([
            'vendor_account_id' => $vendor1->id,
            'offering_id' => $offering->id,
            'title' => 'Profile 1',
            'service_mode' => 'FIXED_LOCATION',
            'is_published' => true,
            'is_approved' => true
        ]);

        // Vendor 2: Roma (does not match, has FIXED_LOCATION, legacy bug includes it, strict excludes it)
        $user2 = new User();
        $user2->name = 'Test User 2';
        $user2->email = 'test2' . uniqid() . '@example.com';
        $user2->password = bcrypt('password');
        $user2->save();

        $vendor2 = VendorAccount::create([
            'user_id' => $user2->id,
            'category_id' => $category->id,
            'company_name' => 'V2',
            'status' => 'ACTIVE',
            'legal_city' => 'Roma'
        ]);
        $vendor2->offerings()->attach($offering->id, ['is_active' => true]);
        VendorOfferingProfile::create([
            'vendor_account_id' => $vendor2->id,
            'offering_id' => $offering->id,
            'title' => 'Profile 2',
            'service_mode' => 'FIXED_LOCATION',
            'is_published' => true,
            'is_approved' => true
        ]);

        // Mock geocoding to return null (triggering fallback)
        $this->mock(\App\Services\GeocodingService::class, function ($mock) {
            $mock->shouldReceive('geocodeCity')->andReturn(null);
        });

        // Mock availability to always return available
        $this->mock(\App\Services\AvailabilityService::class, function ($mock) {
            $mock->shouldReceive('getAvailability')->andReturn([
                '2030-01-01' => [['status' => 'AVAILABLE']]
            ]);
        });

        // Test Legacy Mode
        config(['booking_bridge.geocoding_fallback_mode' => 'legacy']);
        $service = app(\App\Services\VendorSearchService::class);
        $resultLegacy = $service->search(['city' => 'Milano', 'date' => '2030-01-01']);
        
        // Vendor 1 and 2 are returned in legacy (2 total)
        $this->assertEquals(2, $resultLegacy['total']);
        $this->assertTrue($resultLegacy['geocoding_unavailable']);
        $this->assertEquals(1, $resultLegacy['strict_excluded_count']);

        // Test Strict Mode
        config(['booking_bridge.geocoding_fallback_mode' => 'strict']);
        $resultStrict = $service->search(['city' => 'Milano', 'date' => '2030-01-01']);
        
        // Only Vendor 1 is returned in strict
        $this->assertEquals(1, $resultStrict['total']);
        $this->assertTrue($resultStrict['geocoding_unavailable']);
        $this->assertArrayNotHasKey('strict_excluded_count', $resultStrict);
    }
}
