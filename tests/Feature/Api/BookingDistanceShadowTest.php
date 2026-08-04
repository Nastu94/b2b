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

    public function test_legacy_mode_keeps_the_client_distance_without_geocoding(): void
    {
        $geocodingMock = Mockery::mock(GeocodingService::class);
        $resolver = new BookingDistanceResolver($geocodingMock);

        $result = $resolver->resolveForHold(
            new VendorAccount(['legal_city' => 'Milano']),
            new VendorOfferingProfile(['service_mode' => 'MOBILE']),
            'Roma',
            'Lazio',
            42.25,
            'legacy'
        );

        $this->assertSame(42.25, $result['distance_km']);
        $this->assertSame('client_legacy', $result['distance_source']);
        $this->assertNull($result['server_distance_km']);
    }

    public function test_shadow_audits_server_distance_but_keeps_client_distance(): void
    {
        $geocodingMock = Mockery::mock(GeocodingService::class);
        $geocodingMock->shouldReceive('geocodeCity')->with('roma', 'lazio')->once()->andReturn(['lat' => 41.9, 'lng' => 12.5]);
        $geocodingMock->shouldReceive('calculateDistance')->once()->andReturn(500.25);
        $resolver = new BookingDistanceResolver($geocodingMock);

        $result = $resolver->resolveForHold(
            new VendorAccount(['legal_city' => 'Milano', 'legal_lat' => 45.46, 'legal_lng' => 9.19]),
            new VendorOfferingProfile(['service_mode' => 'MOBILE']),
            'Roma',
            'Lazio',
            42.25,
            'shadow'
        );

        $this->assertSame(42.25, $result['distance_km']);
        $this->assertSame(500.25, $result['server_distance_km']);
        $this->assertSame(458.0, $result['delta_km']);
        $this->assertSame('client_shadow', $result['distance_source']);
    }

    public function test_enforce_mode_uses_the_server_distance(): void
    {
        $geocodingMock = Mockery::mock(GeocodingService::class);
        $geocodingMock->shouldReceive('geocodeCity')->with('roma', 'lazio')->once()->andReturn(['lat' => 41.9, 'lng' => 12.5]);
        $geocodingMock->shouldReceive('calculateDistance')->once()->andReturn(500.25);
        $resolver = new BookingDistanceResolver($geocodingMock);

        $result = $resolver->resolveForHold(
            new VendorAccount(['legal_city' => 'Milano', 'legal_lat' => 45.46, 'legal_lng' => 9.19]),
            new VendorOfferingProfile(['service_mode' => 'MOBILE']),
            'Roma',
            'Lazio',
            42.25,
            'enforce'
        );

        $this->assertSame(500.25, $result['distance_km']);
        $this->assertSame('server', $result['distance_source']);
    }
}
