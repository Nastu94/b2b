<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VendorAccount;
use App\Services\VendorDocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VendorDocumentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_storage_ignores_a_spoofed_client_extension(): void
    {
        Storage::fake('local');
        $vendor = VendorAccount::create([
            'user_id' => User::factory()->create()->id,
            'status' => 'PENDING',
        ]);
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9ZQmcAAAAASUVORK5CYII=');
        $file = UploadedFile::fake()
            ->createWithContent('documento.php', $png)
            ->mimeType('image/png');

        $document = app(VendorDocumentService::class)->store(
            $vendor,
            $file,
            ['type' => 'OTHER', 'title' => 'Documento'],
            $vendor->user
        );

        $this->assertStringEndsWith('.png', $document->path);
        $this->assertStringNotContainsString('.php', $document->path);
        Storage::disk('local')->assertExists($document->path);
    }
}
