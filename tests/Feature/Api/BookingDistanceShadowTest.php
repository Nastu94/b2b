<?php

namespace Tests\Feature\Api;

use App\Models\VendorAccount;
use App\Models\VendorOfferingProfile;
use App\Services\BookingDistanceResolver;
use App\Services\GeocodingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class BookingDistanceShadowTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_city_resolution()
    {
        $geocodingMock = Mockery::mock(GeocodingService::class);
        $resolver = new BookingDistanceResolver($geocodingMock);

        $vendor = new VendorAccount(['legal_city' => 'Milano', 'operational_city' => 'Milano']);
        $profile = new VendorOfferingProfile(['service_mode' => 'FIXED_LOCATION']);

        $result = $resolver->resolve($vendor, $profile, 'Milano');

        $this->assertEquals(0.00, $result['distance_km']);
        $this->assertEquals('same_city', $result['distance_source']);
        $this->assertEquals('milano', $result['normalized_city']);
    }

    public function test_server_resolution()
    {
        $geocodingMock = Mockery::mock(GeocodingService::class);
        $geocodingMock->shouldReceive('geocodeCity')->with('roma', null)->andReturn(['lat' => 41.9, 'lng' => 12.5]);
        $geocodingMock->shouldReceive('calculateDistance')->andReturn(500.25);

        $resolver = new BookingDistanceResolver($geocodingMock);

        $vendor = new VendorAccount([
            'legal_city' => 'Milano', 
            'operational_city' => 'Milano',
            'legal_lat' => 45.46,
            'legal_lng' => 9.19
        ]);
        $profile = new VendorOfferingProfile(['service_mode' => 'MOBILE']);

        $result = $resolver->resolve($vendor, $profile, 'Roma');

        $this->assertEquals(500.25, $result['distance_km']);
        $this->assertEquals('server', $result['distance_source']);
    }

    public function test_unresolved()
    {
        $geocodingMock = Mockery::mock(GeocodingService::class);
        $geocodingMock->shouldReceive('geocodeCity')->andReturn(null);

        $resolver = new BookingDistanceResolver($geocodingMock);

        $vendor = new VendorAccount(['legal_city' => 'Milano', 'operational_city' => 'Milano']);
        $profile = new VendorOfferingProfile(['service_mode' => 'MOBILE']);

        $result = $resolver->resolve($vendor, $profile, 'UnknownCity');

        $this->assertNull($result['distance_km']);
        $this->assertEquals('unresolved', $result['distance_source']);
    }
}
