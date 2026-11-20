<?php

namespace Tests\Unit;

use App\Models\GenieacsServer;
use App\Services\Genieacs\GenieacsApiService;
use Tests\TestCase;

class GenieacsDiagnosticsTest extends TestCase
{
    public function test_optical_signal_evaluation(): void
    {
        $service = new GenieacsApiService;

        // Reflection to test protected evaluation methods
        $methodQuality = new \ReflectionMethod(GenieacsApiService::class, 'evaluateSignalQuality');
        $methodQuality->setAccessible(true);

        $methodColor = new \ReflectionMethod(GenieacsApiService::class, 'evaluateSignalColor');
        $methodColor->setAccessible(true);

        // Ideal signal (-20 dBm)
        $this->assertEquals('Sangat Bagus (Ideal)', $methodQuality->invoke($service, '-20.00 dBm'));
        $this->assertEquals('success', $methodColor->invoke($service, '-20.00 dBm'));

        // Warning / fair signal (-24.50 dBm)
        $this->assertEquals('Cukup / Normal', $methodQuality->invoke($service, '-24.50 dBm'));
        $this->assertEquals('warning', $methodColor->invoke($service, '-24.50 dBm'));

        // Poor / bad signal (-28.00 dBm)
        $this->assertEquals('Lemah (Rentan Putus / Redaman Tinggi)', $methodQuality->invoke($service, '-28.00 dBm'));
        $this->assertEquals('danger', $methodColor->invoke($service, '-28.00 dBm'));
    }

    public function test_uptime_formatting(): void
    {
        $service = new GenieacsApiService;
        $method = new \ReflectionMethod(GenieacsApiService::class, 'formatUptime');
        $method->setAccessible(true);

        $this->assertEquals('1 hari 2 jam 30 mnt', $method->invoke($service, 95400));
        $this->assertEquals('N/A', $method->invoke($service, null));
    }

    public function test_simulated_diagnostic_details_structure(): void
    {
        $server = new GenieacsServer([
            'name' => 'Test Server',
            'base_url' => 'http://127.0.0.1:7557',
            'is_active' => true,
        ]);

        $service = new GenieacsApiService;
        $data = $service->getDeviceDiagnosticDetails($server, '202BC1-HG680-ZTEG12345678');

        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('device_info', $data);
        $this->assertArrayHasKey('optical', $data);
        $this->assertArrayHasKey('wan', $data);
        $this->assertArrayHasKey('wifi', $data);
        $this->assertArrayHasKey('connected_hosts', $data);
        $this->assertEquals('success', $data['optical']['quality_color']);
    }
}
