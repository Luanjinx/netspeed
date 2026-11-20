<?php

namespace Tests\Feature;

use App\Filament\Pages\AppSettings;
use App\Models\IspSetting;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AppSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_app_settings_page_mounts_and_saves_api_keys_successfully(): void
    {
        $user = User::first() ?? User::factory()->create();
        $this->actingAs($user);

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(AppSettings::class)
            ->assertSuccessful()
            ->fillForm([
                'whatsapp_gateway_url' => 'https://api.fonnte.com/send',
                'whatsapp_api_key' => 'test_wa_token_12345',
                'payment_gateway_api_url' => 'https://tripay.co.id/api',
                'payment_gateway_server_key' => 'test_server_key_abc',
                'company_name' => 'TEST ISP BROADBAND',
                'static_qris_string' => '00020101021126580016ID.CO.QRIS.WWW01189360099900000000010215202305260000010303UMI51440014ID.CO.QRIS.WWW0215ID10200210000020303UMI5204481453033605802ID5916WARUNG ISP CEPAT6007JAKARTA63041633',
            ])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEquals('https://api.fonnte.com/send', IspSetting::get('whatsapp_gateway_url'));
        $this->assertEquals('test_wa_token_12345', IspSetting::get('whatsapp_api_key'));
        $this->assertEquals('test_server_key_abc', IspSetting::get('payment_gateway_server_key'));
        $this->assertEquals('TEST ISP BROADBAND', IspSetting::get('company_name'));
        $this->assertEquals('00020101021126580016ID.CO.QRIS.WWW01189360099900000000010215202305260000010303UMI51440014ID.CO.QRIS.WWW0215ID10200210000020303UMI5204481453033605802ID5916WARUNG ISP CEPAT6007JAKARTA63041633', IspSetting::get('static_qris_string'));
    }
}
