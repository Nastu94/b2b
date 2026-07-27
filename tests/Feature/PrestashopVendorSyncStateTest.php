<?php

namespace Tests\Feature;

use App\Jobs\PushVendorToPrestashopJob;
use App\Models\Category;
use App\Models\User;
use App\Models\VendorAccount;
use App\Services\PrestashopProductSyncService;
use App\Services\PrestashopWebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use App\Services\PrestashopVendorPayloadFactory;

class PrestashopVendorSyncStateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config(['services.prestashop.webhook_url' => 'https://prestashop.test/webhook']);
        config(['services.prestashop.endpoint' => 'https://prestashop.test/api']);
    }

    public function test_sync_updates_version_and_hash_on_success()
    {
        Http::fake([
            'https://prestashop.test/api*' => Http::response(['product_id' => 123]),
            'https://prestashop.test/webhook' => Http::response(['success' => true]),
        ]);

        $user = User::factory()->create();
        $category = Category::create(['name' => 'Test', 'slug' => 'test', 'prestashop_category_id' => 1]);
        $vendor = VendorAccount::create([
            'user_id' => $user->id,
            'status' => 'ACTIVE',
            'category_id' => $category->id,
            'prestashop_sync_version' => 0,
            'company_name' => 'Test Vendor',
            'legal_country' => 'IT',
        ]);
        
        $vendor->vendorOfferingProfiles()->create([
            'name' => 'Profile',
            'is_published' => true,
            'is_approved' => true,
        ]);

        $job = new PushVendorToPrestashopJob($vendor);
        $job->handle(
            app(PrestashopWebhookService::class),
            app(PrestashopProductSyncService::class),
            app(PrestashopVendorPayloadFactory::class)
        );

        $vendor->refresh();

        $this->assertEquals(1, $vendor->prestashop_sync_version);
        $this->assertNotNull($vendor->prestashop_payload_hash);
        $this->assertNotNull($vendor->prestashop_synced_at);
        $this->assertNull($vendor->prestashop_sync_error_code);
        $this->assertEquals(123, $vendor->prestashop_product_id);
    }

    public function test_sync_retains_version_on_webhook_failure()
    {
        Http::fake([
            'https://prestashop.test/api*' => Http::response(['product_id' => 123]),
            'https://prestashop.test/webhook' => Http::response(['error' => 'Not found'], 404),
        ]);

        $user = User::factory()->create();
        $category = Category::create(['name' => 'Test', 'slug' => 'test', 'prestashop_category_id' => 1]);
        $vendor = VendorAccount::create([
            'user_id' => $user->id,
            'status' => 'ACTIVE',
            'category_id' => $category->id,
            'prestashop_sync_version' => 0,
            'company_name' => 'Test Vendor',
            'legal_country' => 'IT',
        ]);
        
        $vendor->vendorOfferingProfiles()->create([
            'name' => 'Profile',
            'is_published' => true,
            'is_approved' => true,
        ]);

        $job = new PushVendorToPrestashopJob($vendor);
        
        try {
            $job->handle(
                app(PrestashopWebhookService::class),
                app(PrestashopProductSyncService::class),
                app(PrestashopVendorPayloadFactory::class)
            );
            $this->fail('Should have thrown an exception');
        } catch (\Exception $e) {
            // expected
        }

        $vendor->refresh();

        $this->assertEquals(1, $vendor->prestashop_sync_version); // version bumped!
        $this->assertNotNull($vendor->prestashop_payload_hash);
        $this->assertNull($vendor->prestashop_synced_at); // Not successfully synced
        $this->assertNotNull($vendor->prestashop_sync_error_code);
        $this->assertNotNull($vendor->prestashop_sync_error_at);
        $this->assertEquals(123, $vendor->prestashop_product_id); // Native product was created
    }

    public function test_sync_is_skipped_if_hash_is_identical()
    {
        Http::fake([
            'https://prestashop.test/api*' => Http::response(['product_id' => 123]),
            'https://prestashop.test/webhook' => Http::response(['success' => true]),
        ]);

        $user = User::factory()->create();
        $category = Category::create(['name' => 'Test', 'slug' => 'test', 'prestashop_category_id' => 1]);
        $vendor = VendorAccount::create([
            'user_id' => $user->id,
            'status' => 'ACTIVE',
            'category_id' => $category->id,
            'prestashop_sync_version' => 1,
            'prestashop_product_id' => 123,
            'prestashop_sync_error_code' => null,
            'company_name' => 'Test Vendor',
            'legal_country' => 'IT',
        ]);
        
        $vendor->vendorOfferingProfiles()->create([
            'name' => 'Profile',
            'is_published' => true,
            'is_approved' => true,
        ]);

        // Manually calculate what the hash would be
        $factory = app(PrestashopVendorPayloadFactory::class);
        // Next version would be 2
        $payload = $factory->buildPayload($vendor, 2);
        $hash = hash('sha256', json_encode($payload));
        
        // Manually set the hash on vendor to make it seem identical
        $vendor->update(['prestashop_payload_hash' => $hash]);

        $job = new PushVendorToPrestashopJob($vendor);
        $job->handle(
            app(PrestashopWebhookService::class),
            app(PrestashopProductSyncService::class),
            app(PrestashopVendorPayloadFactory::class)
        );

        // Http should not have been called because hash matches
        Http::assertNothingSent();
    }
}
