<?php

namespace Tests\Unit;

use App\Services\Payment\QrisService;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Tests\TestCase;

class QrisServiceTest extends TestCase
{
    protected string $sampleQris = '00020101021126580016ID.CO.QRIS.WWW01189360099900000000010215202305260000010303UMI51440014ID.CO.QRIS.WWW0215ID10200210000020303UMI5204481453033605802ID5916WARUNG ISP CEPAT6007JAKARTA63041633';

    public function test_it_decodes_qris_from_image_file_successfully(): void
    {
        $service = new QrisService;

        // Create temporary PNG file with sample QRIS
        $tempPath = tempnam(sys_get_temp_dir(), 'qris_test_').'.png';
        $options = new QROptions([
            'version' => QRCode::VERSION_AUTO,
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel' => QRCode::ECC_M,
            'addQuietzone' => true,
        ]);
        (new QRCode($options))->render($this->sampleQris, $tempPath);

        $this->assertFileExists($tempPath);

        // Decode from file
        $result = $service->decodeFromImage($tempPath);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['is_emvco']);
        $this->assertEquals($this->sampleQris, $result['payload']);
        $this->assertEquals('WARUNG ISP CEPAT', $result['merchant_name']);
        $this->assertEquals('JAKARTA', $result['merchant_city']);

        @unlink($tempPath);
    }

    public function test_it_decodes_qris_from_binary_blob(): void
    {
        $service = new QrisService;

        $options = new QROptions([
            'version' => QRCode::VERSION_AUTO,
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel' => QRCode::ECC_M,
            'addQuietzone' => true,
        ]);
        $blob = (new QRCode($options))->render($this->sampleQris);

        $result = $service->decodeFromImage($blob);

        $this->assertTrue($result['success']);
        $this->assertEquals('WARUNG ISP CEPAT', $result['merchant_name']);
        $this->assertEquals('JAKARTA', $result['merchant_city']);
    }

    public function test_it_handles_non_emvco_qr_code_gracefully(): void
    {
        $service = new QrisService;

        $urlQr = 'https://google.com';
        $options = new QROptions([
            'version' => QRCode::VERSION_AUTO,
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel' => QRCode::ECC_M,
            'addQuietzone' => true,
        ]);
        $blob = (new QRCode($options))->render($urlQr);

        $result = $service->decodeFromImage($blob);

        $this->assertFalse($result['success']);
        $this->assertFalse($result['is_emvco']);
        $this->assertStringContainsString('bukan format QRIS', $result['message']);
    }

    public function test_it_extracts_qris_details_and_validates_crc(): void
    {
        $service = new QrisService;
        $details = $service->getQrisDetails($this->sampleQris);

        $this->assertTrue($details['is_emvco']);
        $this->assertEquals('WARUNG ISP CEPAT', $details['merchant_name']);
        $this->assertEquals('JAKARTA', $details['merchant_city']);
        $this->assertEquals('Statis', $details['initiation_method']);
        $this->assertEquals('1633', $details['crc']);
        $this->assertTrue($details['crc_valid']);
    }
}
