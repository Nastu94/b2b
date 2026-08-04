<?php

namespace Tests\Feature;

use App\Jobs\PushVendorToPrestashopJob;
use App\Models\Category;
use App\Models\Offering;
use App\Models\User;
use App\Models\VendorAccount;
use App\Services\PrestashopProductSyncService;
use App\Services\PrestashopWebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use App\Services\PrestashopVendorPayloadFactory;
use App\Services\OfferingApprovalService;

class PrestashopVendorSyncStateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        Queue::fake();
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
        $offering = Offering::create(['category_id' => $category->id, 'name' => 'Servizio', 'slug' => 'servizio-1', 'is_active' => true]);
        $vendor = VendorAccount::create([
            'user_id' => $user->id,
            'status' => 'ACTIVE',
            'category_id' => $category->id,
            'prestashop_sync_version' => 0,
            'company_name' => 'Test Vendor',
            'legal_country' => 'IT',
        ]);
        
        $vendor->vendorOfferingProfiles()->create([
            'offering_id' => $offering->id,
            'title' => 'Profile',
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
        $offering = Offering::create(['category_id' => $category->id, 'name' => 'Servizio', 'slug' => 'servizio-2', 'is_active' => true]);
        $vendor = VendorAccount::create([
            'user_id' => $user->id,
            'status' => 'ACTIVE',
            'category_id' => $category->id,
            'prestashop_sync_version' => 0,
            'company_name' => 'Test Vendor',
            'legal_country' => 'IT',
        ]);
        
        $vendor->vendorOfferingProfiles()->create([
            'offering_id' => $offering->id,
            'title' => 'Profile',
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
        $offering = Offering::create(['category_id' => $category->id, 'name' => 'Servizio', 'slug' => 'servizio-3', 'is_active' => true]);
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
            'offering_id' => $offering->id,
            'title' => 'Profile',
            'is_published' => true,
            'is_approved' => true,
        ]);

        // Manually calculate what the hash would be
        $factory = app(PrestashopVendorPayloadFactory::class);
        // Next version would be 2
        $payload = $factory->buildPayload($vendor->fresh(['category', 'vendorOfferingProfiles.images']), 2);
        $hash = $factory->contentHash($payload);
        
        // Manually set the hash on vendor to make it seem identical
        $vendor->forceFill(['prestashop_payload_hash' => $hash])->saveQuietly();

        $job = new PushVendorToPrestashopJob($vendor);
        $job->handle(
            app(PrestashopWebhookService::class),
            app(PrestashopProductSyncService::class),
            app(PrestashopVendorPayloadFactory::class)
        );

        // Http should not have been called because hash matches
        Http::assertNothingSent();
    }

    public function test_active_vendor_with_published_service_requires_prestashop_category_mapping(): void
    {
        $category = Category::create(['name' => 'Non mappata', 'slug' => 'non-mappata']);
        $offering = Offering::create([
            'category_id' => $category->id,
            'name' => 'Servizio',
            'slug' => 'servizio-non-mappato',
            'is_active' => true,
        ]);
        $vendor = VendorAccount::create([
            'user_id' => User::factory()->create()->id,
            'status' => 'ACTIVE',
            'category_id' => $category->id,
            'company_name' => 'Vendor senza mapping',
        ]);
        $vendor->vendorOfferingProfiles()->create([
            'offering_id' => $offering->id,
            'title' => 'Profilo',
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
        $this->assertStringContainsString('Categoria PrestaShop mancante', $vendor->prestashop_sync_error_code);
        $this->assertNotNull($vendor->prestashop_sync_error_at);
        $this->assertNull($vendor->prestashop_synced_at);

        Http::assertNothingSent();
    }

    public function test_prestashop_category_fallback_is_treated_as_sync_error(): void
    {
        Http::fake([
            'https://prestashop.test/api*' => Http::response([
                'success' => true,
                'product_id' => 456,
                'category_fallback_used' => true,
            ]),
        ]);

        $category = Category::create([
            'name' => 'Mappata',
            'slug' => 'mappata',
            'prestashop_category_id' => 99,
        ]);
        $offering = Offering::create([
            'category_id' => $category->id,
            'name' => 'Servizio',
            'slug' => 'servizio-fallback',
            'is_active' => true,
        ]);
        $vendor = VendorAccount::create([
            'user_id' => User::factory()->create()->id,
            'status' => 'ACTIVE',
            'category_id' => $category->id,
            'company_name' => 'Vendor fallback',
        ]);
        $vendor->vendorOfferingProfiles()->create([
            'offering_id' => $offering->id,
            'title' => 'Profilo',
            'is_published' => true,
            'is_approved' => true,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('categoria di fallback');

        app(PrestashopProductSyncService::class)->sync($vendor, ['sync_version' => 1]);
    }

    public function test_rejecting_service_dispatches_catalog_resynchronization(): void
    {
        $category = Category::create([
            'name' => 'Test',
            'slug' => 'reject-sync',
            'prestashop_category_id' => 1,
        ]);
        $offering = Offering::create([
            'category_id' => $category->id,
            'name' => 'Servizio',
            'slug' => 'servizio-reject-sync',
            'is_active' => true,
        ]);
        $vendor = VendorAccount::create([
            'user_id' => User::factory()->create()->id,
            'status' => 'ACTIVE',
            'category_id' => $category->id,
            'company_name' => 'Vendor reject sync',
        ]);
        $vendor->offerings()->attach($offering->id, ['is_active' => true]);
        $vendor->vendorOfferingProfiles()->create([
            'offering_id' => $offering->id,
            'title' => 'Profilo',
            'is_published' => true,
            'is_approved' => true,
        ]);
        Queue::fake();

        app(OfferingApprovalService::class)->rejectOfferingProfile($vendor, $offering->id);

        Queue::assertPushed(
            PushVendorToPrestashopJob::class,
            fn (PushVendorToPrestashopJob $job) => $job->vendorAccountId === $vendor->id
        );
    }
}
