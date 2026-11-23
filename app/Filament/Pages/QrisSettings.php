<?php

namespace App\Filament\Pages;

use App\Models\IspSetting;
use App\Services\Payment\QrisService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use UnitEnum;

class QrisSettings extends Page implements HasForms
{
    use InteractsWithForms;
    use WithFileUploads;

    protected static ?string $slug = 'qris-settings';

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-qr-code';

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Billing & QRIS Merchant';

    protected static ?string $title = 'Billing & QRIS Dinamis Otomatis';

    protected ?string $subheading = 'Unggah foto QRIS toko Anda, auto-scan kode EMVCo Bank Indonesia, dan atur 3 digit kode unik nominal tagihan';

    protected string $view = 'filament.pages.qris-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'qris_image_upload' => IspSetting::get('qris_image_upload', null),
            'static_qris_string' => IspSetting::get('static_qris_string', ''),
            'use_unique_code' => IspSetting::get('use_unique_code', '1') === '1',
            'default_isolir_profile' => IspSetting::get('default_isolir_profile', 'PROFIL-ISOLIR'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Grid::make(['default' => 1, 'lg' => 2])->schema([
                    Section::make('QRIS Dinamis Otomatis & Auto Scanner')
                        ->columnSpan(['default' => 1, 'lg' => 2])
                        ->icon('heroicon-o-qr-code')
                        ->description('Unggah gambar atau foto screenshot barcode QRIS toko Anda (BCA, Mandiri, BRI, ShopeePay, GoPay, Nobu, dll). Sistem akan otomatis membaca string EMVCo dan info merchant.')
                        ->schema([
                            Radio::make('qris_input_mode')
                                ->label('Metode Input Gambar QRIS')
                                ->options([
                                    'upload' => 'Upload Langsung (File dari Perangkat)',
                                    'url' => 'Direct URL (Tautan Gambar QRIS)',
                                ])
                                ->default('upload')
                                ->inline()
                                ->live(),

                            FileUpload::make('qris_image_upload')
                                ->label('Upload Foto / Barcode QRIS Merchant (Scan Otomatis)')
                                ->image()
                                ->disk('public')
                                ->directory('qris-uploads')
                                ->visibility('public')
                                ->maxSize(5120)
                                ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg', 'image/webp'])
                                ->live()
                                ->visible(fn ($get) => $get('qris_input_mode') !== 'url')
                                ->helperText('Pilih atau drag & drop file foto/screenshot barcode QRIS toko Anda. Sistem akan langsung membaca QR code dan mengisi string EMVCo secara instan.')
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if (! $state) {
                                        return;
                                    }

                                    $filePath = self::resolveUploadedFilePath($state);
                                    if (! $filePath || ! file_exists($filePath)) {
                                        return;
                                    }

                                    $service = app(QrisService::class);
                                    $result = $service->decodeFromImage($filePath);

                                    if ($result['success'] ?? false) {
                                        $set('static_qris_string', $result['payload']);

                                        Notification::make()
                                            ->title('✅ Barcode QRIS Berhasil Dipindai!')
                                            ->body("Merchant: {$result['merchant_name']}\nKota: {$result['merchant_city']}\nFormat EMVCo valid telah diterapkan ke sistem.")
                                            ->success()
                                            ->duration(8000)
                                            ->send();
                                    } else {
                                        Notification::make()
                                            ->title('Gagal Memindai QR Code')
                                            ->body($result['message'] ?? 'Tidak ditemukan QR code yang valid pada gambar.')
                                            ->danger()
                                            ->duration(9000)
                                            ->send();
                                    }
                                }),

                            TextInput::make('qris_image_url')
                                ->label('Direct URL Gambar Barcode QRIS')
                                ->placeholder('https://example.com/qris-merchant.png')
                                ->url()
                                ->visible(fn ($get) => $get('qris_input_mode') === 'url')
                                ->helperText('Masukkan tautan langsung gambar QRIS, lalu klik tombol Pindai URL di sebelah kanan.')
                                ->suffixAction(
                                    Action::make('scanUrl')
                                        ->label('Pindai')
                                        ->icon('heroicon-o-camera')
                                        ->action(function ($state, callable $set) {
                                            if (empty(trim($state ?? ''))) {
                                                Notification::make()->title('Masukkan URL Gambar QRIS')->warning()->send();

                                                return;
                                            }
                                            $tempPath = null;
                                            try {
                                                $response = Http::timeout(15)->get(trim($state));
                                                if (! $response->successful()) {
                                                    Notification::make()->title('Gagal mengunduh gambar dari URL')->danger()->send();

                                                    return;
                                                }
                                                $tempPath = tempnam(sys_get_temp_dir(), 'qris_').'.png';
                                                file_put_contents($tempPath, $response->body());
                                                $service = app(QrisService::class);
                                                $result = $service->decodeFromImage($tempPath);
                                                if ($result['success'] ?? false) {
                                                    $set('static_qris_string', $result['payload']);
                                                    Notification::make()
                                                        ->title('✅ Barcode QRIS Berhasil Dipindai!')
                                                        ->body("Merchant: {$result['merchant_name']}\nKota: {$result['merchant_city']}")
                                                        ->success()
                                                        ->send();
                                                } else {
                                                    Notification::make()
                                                        ->title('Gagal Memindai QR Code')
                                                        ->body($result['message'] ?? 'Tidak ditemukan QR code yang valid.')
                                                        ->danger()
                                                        ->send();
                                                }
                                            } catch (\Throwable $e) {
                                                Notification::make()->title('Error: '.$e->getMessage())->danger()->send();
                                            } finally {
                                                if ($tempPath && file_exists($tempPath)) {
                                                    @unlink($tempPath);
                                                }
                                            }
                                        })
                                ),

                            Placeholder::make('qris_preview_display')
                                ->label('Status & Preview Barcode QRIS Merchant')
                                ->content(fn ($get) => AppSettings::renderQrisPreviewHtml($get('static_qris_string'))),

                            Textarea::make('static_qris_string')
                                ->label('Raw String QRIS Statis EMVCo Merchant')
                                ->placeholder('00020101021126680016ID.CO.QRIS.WWW0118936009990000000001...')
                                ->rows(3)
                                ->live(debounce: 500)
                                ->helperText('Payload EMVCo QRIS resmi toko Anda. Nilai ini otomatis terisi saat foto QRIS diunggah di atas. Anda juga dapat mengubahnya secara manual jika diperlukan.'),

                            Toggle::make('use_unique_code')
                                ->label('Gunakan 3 Digit Kode Unik Tagihan')
                                ->helperText('Menambahkan 3 digit angka acak (cth: Rp 150.245) untuk mempermudah deteksi pembayaran mutasi rekening.')
                                ->default(true),
                        ]),

                    Section::make('Profil Isolir Otomatis MikroTik')
                        ->icon('heroicon-o-shield-exclamation')
                        ->description('Profil kecepatan yang diterapkan ke pelanggan PPPoE saat menunggak pembayaran')
                        ->schema([
                            TextInput::make('default_isolir_profile')
                                ->label('Nama Profil Isolir MikroTik (/ppp/profile)')
                                ->placeholder('cth: PROFIL-ISOLIR atau ISOLIR')
                                ->required(),
                        ]),
                ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('backToSettings')
                ->label('Kembali ke Pengaturan')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(url('/admin/settings')),

            Action::make('scanQrisImage')
                ->label('Pindai Foto QRIS')
                ->icon('heroicon-o-camera')
                ->color('warning')
                ->modalHeading('Pindai & Ekstrak Data QRIS Merchant')
                ->modalDescription('Unggah file foto atau screenshot barcode QRIS toko Anda. Sistem akan memindai QR code dan menerapkan hasilnya langsung.')
                ->modalSubmitActionLabel('Pindai & Terapkan Otomatis')
                ->form([
                    FileUpload::make('modal_qris_image')
                        ->label('File Foto / Screenshot QRIS')
                        ->image()
                        ->disk('public')
                        ->directory('qris-uploads')
                        ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg', 'image/webp'])
                        ->required(),
                ])
                ->action(function (array $data) {
                    $state = $data['modal_qris_image'] ?? null;
                    $filePath = self::resolveUploadedFilePath($state);

                    if (! $filePath || ! file_exists($filePath)) {
                        Notification::make()->title('File gambar tidak ditemukan')->danger()->send();

                        return;
                    }

                    $service = app(QrisService::class);
                    $res = $service->decodeFromImage($filePath);

                    if ($res['success']) {
                        $this->data['static_qris_string'] = $res['payload'];
                        $this->data['qris_image_upload'] = $state;
                        IspSetting::set('static_qris_string', $res['payload']);
                        if (is_string($state)) {
                            IspSetting::set('qris_image_upload', $state);
                        }

                        Notification::make()
                            ->title('✅ Barcode QRIS Berhasil Diterapkan!')
                            ->body("Merchant: {$res['merchant_name']}\nKota: {$res['merchant_city']}\nPengaturan QRIS telah otomatis diperbarui.")
                            ->success()
                            ->duration(8000)
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Gagal Memindai QRIS')
                            ->body($res['message'])
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('save')
                ->label('Simpan Pengaturan QRIS')
                ->icon('heroicon-o-check')
                ->color('primary')
                ->action(fn () => $this->save()),
        ];
    }

    public function save(): void
    {
        $state = $this->form->getState();

        if (isset($state['qris_image_upload']) && is_string($state['qris_image_upload'])) {
            IspSetting::set('qris_image_upload', $state['qris_image_upload']);
        }
        IspSetting::set('static_qris_string', trim($state['static_qris_string'] ?? ''));
        IspSetting::set('use_unique_code', ! empty($state['use_unique_code']) ? '1' : '0');
        IspSetting::set('default_isolir_profile', trim($state['default_isolir_profile'] ?? 'PROFIL-ISOLIR'));
        IspSetting::set('isolir_profile', trim($state['default_isolir_profile'] ?? 'PROFIL-ISOLIR'));

        Notification::make()
            ->title('Pengaturan Billing & QRIS Berhasil Disimpan!')
            ->success()
            ->send();
    }

    protected static function resolveUploadedFilePath(mixed $state): ?string
    {
        if ($state instanceof TemporaryUploadedFile) {
            return $state->getRealPath();
        }

        if (is_string($state)) {
            $storage = Storage::disk('public');
            if ($storage->exists($state)) {
                return $storage->path($state);
            }
            $fullPath = storage_path('app/public/'.$state);
            if (file_exists($fullPath)) {
                return $fullPath;
            }
        }

        return null;
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin/settings') => 'Pengaturan Sistem',
            'Billing & QRIS Merchant',
        ];
    }
}
