<?php

namespace Tests\Feature;

use App\Filament\Pages\AppSettings;
use App\Models\IspSetting;
use App\Models\User;
use App\Services\Payment\QrisService;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class QrisAutoDecodeTest extends TestCase
{
    use RefreshDatabase;

    protected string $sampleQris = '00020101021126580016ID.CO.QRIS.WWW01189360099900000000010215202305260000010303UMI51440014ID.CO.QRIS.WWW0215ID10200210000020303UMI5204481453033605802ID5916WARUNG ISP CEPAT6007JAKARTA63041633';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_qris_preview_html_renders_empty_and_active_states(): void
    {
        // 1. Empty state
        $emptyHtml = AppSettings::renderQrisPreviewHtml(null)->toHtml();
        $this->assertStringContainsString('Belum Ada Barcode QRIS', $emptyHtml);

        // 2. Active EMVCo state
        $activeHtml = AppSettings::renderQrisPreviewHtml($this->sampleQris)->toHtml();
        $this->assertStringContainsString('WARUNG ISP CEPAT', $activeHtml);
        $this->assertStringContainsString('JAKARTA', $activeHtml);
        $this->assertStringContainsString('Standar EMVCo QRIS Terverifikasi', $activeHtml);
        $this->assertStringContainsString('data:image/svg+xml;base64,', $activeHtml);
    }

    public function test_scan_qris_action_decodes_uploaded_image_and_saves_settings(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        // Generate temporary PNG image with sample QR code
        $options = new QROptions([
            'version' => QRCode::VERSION_AUTO,
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel' => QRCode::ECC_M,
            'addQuietzone' => true,
            'outputBase64' => false,
        ]);
        $pngBinary = (new QRCode($options))->render($this->sampleQris);

        $fakeFile = UploadedFile::fake()->createWithContent('qris_sample.png', $pngBinary);

        // Store file in public disk as FileUpload would
        $storedPath = $fakeFile->store('qris-uploads', 'public');
        $this->assertNotNull($storedPath);
        $this->assertTrue(Storage::disk('public')->exists($storedPath));

        // Call the scanQrisImage action with array value for FileUpload
        Livewire::test(AppSettings::class)
            ->callAction('scanQrisImage', [
                'modal_qris_image' => [$storedPath],
            ])
            ->assertHasNoActionErrors();

        // Check that IspSetting::get('static_qris_string') was updated with the decoded payload
        $this->assertEquals($this->sampleQris, IspSetting::get('static_qris_string'));
    }

    public function test_resolve_uploaded_file_path_and_service_decode(): void
    {
        $options = new QROptions([
            'version' => QRCode::VERSION_AUTO,
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel' => QRCode::ECC_M,
            'addQuietzone' => true,
            'outputBase64' => false,
        ]);
        $pngBinary = (new QRCode($options))->render($this->sampleQris);

        $fakeFile = UploadedFile::fake()->createWithContent('qris_helper.png', $pngBinary);
        $storedPath = $fakeFile->store('qris-uploads', 'public');

        // Test resolveUploadedFilePath with string, array, and nested array
        $resolved = AppSettings::resolveUploadedFilePath($storedPath);
        $this->assertNotNull($resolved);
        $this->assertFileExists($resolved);

        $resolvedArray = AppSettings::resolveUploadedFilePath([$storedPath]);
        $this->assertEquals($resolved, $resolvedArray);

        // Test QrisService decode from this resolved path
        $service = app(QrisService::class);
        $result = $service->decodeFromImage($resolved);

        $this->assertTrue($result['success']);
        $this->assertEquals($this->sampleQris, $result['payload']);
        $this->assertEquals('WARUNG ISP CEPAT', $result['merchant_name']);
        $this->assertEquals('JAKARTA', $result['merchant_city']);
    }
}
