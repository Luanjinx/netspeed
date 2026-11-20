<?php

namespace App\Filament\Resources\IspSettings\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class IspSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Konfigurasi Parameter Sistem & API')
                    ->description('Pengaturan API Link, API Key, QRIS, dan profil ISP')
                    ->schema([
                        Select::make('key')
                            ->label('Kunci Parameter (Key)')
                            ->options([
                                // WhatsApp Gateway
                                'whatsapp_gateway_url' => '🔗 [WhatsApp] Link URL Gateway API (Endpoint)',
                                'whatsapp_api_key' => '🔑 [WhatsApp] API Key / Token Otorisasi',
                                'whatsapp_sender_number' => '📱 [WhatsApp] Nomor Pengirim / Sender',

                                // Payment Gateway
                                'payment_gateway_provider' => '💳 [Payment] Provider Payment Gateway',
                                'payment_gateway_api_url' => '🔗 [Payment] Link URL Endpoint Gateway',
                                'payment_gateway_server_key' => '🔑 [Payment] Server Key / Secret Key',
                                'payment_gateway_client_key' => '🔑 [Payment] Client Key',
                                'payment_gateway_merchant_code' => '🏪 [Payment] Merchant ID / Code',

                                // Mobile Flutter API
                                'app_api_base_url' => '🔗 [Mobile App] Base URL API Backend (Untuk Flutter / STB)',
                                'app_api_key' => '🔑 [Mobile App] API Secret Key Client',
                                'fcm_server_key' => '🔔 [Mobile App] Firebase Cloud Messaging (FCM) Key',

                                // GenieACS TR-069
                                'genieacs_api_url' => '🔗 [GenieACS] Link URL NBI Server',
                                'genieacs_auth_token' => '🔑 [GenieACS] Auth Token / API Key',
                                'genieacs_username' => '👤 [GenieACS] Username Basic Auth',
                                'genieacs_password' => '🔒 [GenieACS] Password Basic Auth',

                                // Maps
                                'google_maps_api_key' => '🔑 [Maps] Google Maps API Key',

                                // Company & Office
                                'company_name' => '🏢 [Profil] Nama Perusahaan / ISP',
                                'company_phone' => '📞 [Profil] Nomor CS / Bantuan',
                                'company_address' => '📍 [Profil] Alamat Kantor',
                                'office_lat' => '🌐 [Profil] Latitude Kantor Pusat (Peta)',
                                'office_lng' => '🌐 [Profil] Longitude Kantor Pusat (Peta)',

                                // Billing
                                'static_qris_string' => '💳 [Billing] Raw String QRIS Statis EMVCo',
                                'use_unique_code' => '🔢 [Billing] Gunakan Kode Unik Nominal (1/0)',
                                'default_isolir_profile' => '🚫 [Billing] Profil Isolir MikroTik Default',
                                'isolir_profile' => '🚫 [Billing] Profil Isolir MikroTik (Alias)',
                            ])
                            ->searchable()
                            ->required(),

                        Textarea::make('value')
                            ->label('Nilai Pengaturan (Value / URL / Key)')
                            ->rows(4)
                            ->helperText('Masukkan nilai link URL, API Key rahasia, atau data konfigurasi parameter.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
