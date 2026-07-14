<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LegalDocumentAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a dummy authorized file
        $path = storage_path('app/legal/vendor');
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        file_put_contents($path . '/codice-etico-partylegacy.pdf', 'dummy pdf content');
    }

    protected function tearDown(): void
    {
        $path = storage_path('app/legal/vendor/codice-etico-partylegacy.pdf');
        if (file_exists($path)) {
            @unlink($path);
        }
        parent::tearDown();
    }

    public function test_authorized_and_existing_pdf_returns_200()
    {
        $response = $this->get('/legal/vendor/file/codice-etico-partylegacy.pdf');
        
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_unauthorized_filename_returns_404()
    {
        $response = $this->get('/legal/vendor/file/non-esistente.pdf');
        
        $response->assertStatus(404);
    }

    public function test_directory_traversal_attempt_returns_404()
    {
        $response = $this->get('/legal/vendor/file/../private/vendor-documents/test.png');
        
        $response->assertStatus(404);
    }

    public function test_authorized_but_missing_file_returns_404()
    {
        $response = $this->get('/legal/vendor/file/policy-catering-partylegacy.pdf');
        
        $response->assertStatus(404);
    }
}
