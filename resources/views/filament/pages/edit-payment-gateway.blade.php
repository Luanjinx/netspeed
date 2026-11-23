<x-filament-panels::page>
    <style>
        .pg-edit-container {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            max-width: 1100px;
            margin: 0 auto;
            width: 100%;
        }

        .pg-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .pg-card-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            background: var(--surface2);
        }

        .pg-card-title {
            font-size: 1rem;
            font-weight: 800;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 0.6rem;
            margin: 0;
        }

        .pg-card-body {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .pg-label {
            font-size: 0.75rem;
            font-weight: 800;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.4rem;
            display: block;
        }

        .pg-input, .pg-select, .pg-textarea {
            width: 100%;
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 0.625rem;
            padding: 0.65rem 0.875rem;
            color: var(--text);
            font-size: 0.875rem;
            transition: all 0.15s ease;
            box-sizing: border-box;
        }

        .pg-input:focus, .pg-select:focus, .pg-textarea:focus {
            outline: none;
            border-color: #00e07e;
            box-shadow: 0 0 0 2px rgba(0, 224, 126, 0.2);
            background: var(--surface);
        }

        .pg-grid-2 {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1.25rem;
        }

        @media (max-width: 768px) {
            .pg-grid-2 {
                grid-template-columns: 1fr;
            }
        }

        .pg-btn-primary {
            background: #00e07e;
            color: #06261a;
            font-weight: 800;
            font-size: 0.875rem;
            padding: 0.65rem 1.5rem;
            border-radius: 0.625rem;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.15s ease;
        }

        .pg-btn-primary:hover {
            background: #4deda4;
            color: #06261a;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 224, 126, 0.3);
        }

        .pg-btn-secondary {
            background: var(--surface2);
            color: var(--text);
            font-weight: 700;
            font-size: 0.875rem;
            padding: 0.65rem 1.25rem;
            border-radius: 0.625rem;
            border: 1px solid var(--border);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .pg-btn-secondary:hover {
            background: var(--border);
            color: var(--text);
        }

        .pg-pill-badge {
            font-size: 0.6875rem;
            font-weight: 800;
            padding: 0.25rem 0.65rem;
            border-radius: 9999px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .pg-pill-active {
            background: #00e07e;
            color: #06261a;
        }

        .pg-pill-inactive {
            background: var(--surface2);
            color: var(--muted);
            border: 1px solid var(--border);
        }

        .pg-hero-card {
            padding: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1.25rem;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 1rem;
            box-shadow: var(--shadow);
        }
        .dark .pg-hero-card {
            background: linear-gradient(135deg, #161a18 0%, var(--surface) 100%);
        }

        .pg-switch-box {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            cursor: pointer;
            user-select: none;
            background: var(--surface2);
            border: 1px solid var(--border);
            padding: 0.65rem 1rem;
            border-radius: 0.75rem;
        }

        .pg-subbox {
            background: var(--surface2);
            border: 1px dashed var(--border);
            border-radius: 0.75rem;
            padding: 1rem;
        }
    </style>

    <div class="pg-edit-container">
        <!-- 1. TOP HEADER & BREADCRUMB NAVIGATION -->
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <a href="{{ \App\Filament\Pages\PaymentGatewaySettings::getUrl() }}" wire:navigate class="pg-btn-secondary" style="padding: 0.5rem 0.875rem;">
                    <span class="material-symbols-rounded" style="font-size: 18px;">arrow_back</span>
                    <span>Kembali ke Daftar Gateway</span>
                </a>
            </div>

            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <button type="button" wire:click="simulateWebhook" class="pg-btn-secondary" style="color: #f59e0b; border-color: rgba(245,158,11,0.4);">
                    <span class="material-symbols-rounded" style="font-size: 18px;">bolt</span>
                    <span>Simulasi Webhook Lunas</span>
                </button>
            </div>
        </div>

        <!-- 2. GATEWAY IDENTITY HERO BANNER -->
        <div class="pg-hero-card">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <div style="width: 56px; height: 56px; border-radius: 0.875rem; background: #ffffff; border: 1.5px solid var(--border); display: flex; align-items: center; justify-content: center; overflow: hidden; padding: 6px;">
                    @if ($gateway && $gateway->logo_url)
                        <img src="{{ $gateway->logo_url }}" alt="{{ $name }}" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                    @elseif ($code === 'qris_manual')
                        <span class="material-symbols-rounded" style="font-size: 32px; color: #00e07e;">qr_code_2</span>
                    @elseif ($code === 'manual_transfer')
                        <span class="material-symbols-rounded" style="font-size: 32px; color: #38bdf8;">account_balance</span>
                    @elseif ($code === 'tripay')
                        <span class="material-symbols-rounded" style="font-size: 32px; color: #a855f7;">account_balance_wallet</span>
                    @elseif ($code === 'duitku')
                        <span class="material-symbols-rounded" style="font-size: 32px; color: #f97316;">credit_card</span>
                    @elseif ($code === 'midtrans')
                        <span class="material-symbols-rounded" style="font-size: 32px; color: #0284c7;">payments</span>
                    @elseif ($code === 'xendit')
                        <span class="material-symbols-rounded" style="font-size: 32px; color: #10b981;">wallet</span>
                    @else
                        <span class="material-symbols-rounded" style="font-size: 32px; color: var(--text);">toll</span>
                    @endif
                </div>

                <div>
                    <div style="display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap;">
                        <h2 style="font-size: 1.25rem; font-weight: 800; color: var(--text); margin: 0;">{{ $name }}</h2>
                        <span class="pg-pill-badge {{ $isActive ? 'pg-pill-active' : 'pg-pill-inactive' }}">
                            {{ $isActive ? 'Active' : 'Inactive' }}
                        </span>
                        <code style="font-family: ui-monospace, monospace; font-size: 0.6875rem; color: #0284c7; background: rgba(14,165,233,0.1); border: 1px solid rgba(14,165,233,0.25); padding: 0.15rem 0.45rem; border-radius: 0.25rem;">
                            {{ $code }}
                        </code>
                    </div>
                    <p style="font-size: 0.8125rem; color: var(--muted); margin: 0.25rem 0 0 0;">
                        Kategori: <strong style="color: var(--text);">{{ strtoupper($category) }}</strong> &bull; Saluran pembayaran aktif untuk tagihan internet pelanggan NetSpeed.
                    </p>
                </div>
            </div>

            <!-- Instant Toggle Active Switch -->
            <label class="pg-switch-box">
                <input type="checkbox" wire:model.live="isActive" style="width: 1.2rem; height: 1.2rem; accent-color: #00e07e; cursor: pointer;">
                <div>
                    <div style="font-size: 0.8125rem; font-weight: 800; color: var(--text);">Status Gateway</div>
                    <div style="font-size: 0.6875rem; color: var(--muted);">{{ $isActive ? 'Aktif (Ditampilkan ke Pelanggan)' : 'Nonaktif (Disembunyikan)' }}</div>
                </div>
            </label>
        </div>

        <form wire:submit="save" style="display: flex; flex-direction: column; gap: 1.5rem;">
            <!-- 3. INFORMASI DASAR GATEWAY -->
            <div class="pg-card">
                <div class="pg-card-header">
                    <h3 class="pg-card-title">
                        <span class="material-symbols-rounded" style="color: #00e07e;">badge</span>
                        <span>Informasi Gateway</span>
                    </h3>
                </div>
                <div class="pg-card-body">
                    <div class="pg-grid-2">
                        <div>
                            <label class="pg-label">Nama Tampilan Gateway *</label>
                            <input type="text" wire:model="name" class="pg-input" placeholder="Contoh: QRIS Dinamis Mandiri, TriPay, dll" required>
                            <span style="font-size: 0.6875rem; color: var(--muted); margin-top: 0.25rem; display: block;">Nama yang akan dilihat oleh pelanggan saat memilih metode pembayaran.</span>
                        </div>
                        <div>
                            <label class="pg-label">Kode Unik Sistem</label>
                            <input type="text" value="{{ $code }}" class="pg-input" disabled style="opacity: 0.6; cursor: not-allowed;">
                            <span style="font-size: 0.6875rem; color: var(--muted); margin-top: 0.25rem; display: block;">Identifier internal sistem untuk pemrosesan webhook & transaksi.</span>
                        </div>
                    </div>

                    <div>
                        <label class="pg-label">Deskripsi Singkat untuk Pelanggan</label>
                        <input type="text" wire:model="description" class="pg-input" placeholder="Contoh: Pembayaran instan mendukung semua E-Wallet & Mobile Banking nasional">
                        <span style="font-size: 0.6875rem; color: var(--muted); margin-top: 0.25rem; display: block;">Keterangan yang tampil di bawah nama gateway pada aplikasi mobile pelanggan.</span>
                    </div>
                </div>
            </div>

            <!-- 4. LOGO / BRANDING GATEWAY (UPLOAD LANGSUNG ATAU DIRECT URL) -->
            <div class="pg-card">
                <div class="pg-card-header">
                    <h3 class="pg-card-title">
                        <span class="material-symbols-rounded" style="color: #00e07e;">image</span>
                        <span>Logo / Ikon Gateway (Upload Langsung atau Direct URL)</span>
                    </h3>
                </div>
                <div class="pg-card-body">
                    <div>
                        <label class="pg-label">Pilih Metode Input Gambar Logo *</label>
                        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 0.75rem;">
                            <label style="display: flex; align-items: center; gap: 0.5rem; background: {{ $logoType === 'upload' ? 'rgba(0,224,126,0.12)' : 'var(--surface2)' }}; border: 1.5px solid {{ $logoType === 'upload' ? '#00e07e' : 'var(--border)' }}; padding: 0.65rem 1.1rem; border-radius: 0.625rem; cursor: pointer; color: {{ $logoType === 'upload' ? '#059669' : 'var(--text)' }}; font-weight: 700; font-size: 0.8125rem; transition: all 0.15s ease;">
                                <input type="radio" wire:model.live="logoType" value="upload" style="accent-color: #00e07e;">
                                <span class="material-symbols-rounded" style="font-size: 18px;">cloud_upload</span>
                                <span>Upload Langsung (Berkas dari Perangkat)</span>
                            </label>

                            <label style="display: flex; align-items: center; gap: 0.5rem; background: {{ $logoType === 'url' ? 'rgba(0,224,126,0.12)' : 'var(--surface2)' }}; border: 1.5px solid {{ $logoType === 'url' ? '#00e07e' : 'var(--border)' }}; padding: 0.65rem 1.1rem; border-radius: 0.625rem; cursor: pointer; color: {{ $logoType === 'url' ? '#059669' : 'var(--text)' }}; font-weight: 700; font-size: 0.8125rem; transition: all 0.15s ease;">
                                <input type="radio" wire:model.live="logoType" value="url" style="accent-color: #00e07e;">
                                <span class="material-symbols-rounded" style="font-size: 18px;">link</span>
                                <span>Direct URL (Tautan Gambar Eksternal)</span>
                            </label>
                        </div>
                    </div>

                    @if ($logoType === 'upload')
                        <div>
                            <label class="pg-label">Unggah File Gambar Logo</label>
                            <input type="file" wire:model="logoFile" accept="image/*" class="pg-input" style="padding: 0.45rem;">
                            <span style="font-size: 0.6875rem; color: var(--muted); margin-top: 0.25rem; display: block;">Format didukung: PNG, JPG, JPEG, WEBP, SVG (Maks. 2MB). Disarankan berlatar transparan atau terang.</span>
                        </div>
                    @else
                        <div>
                            <label class="pg-label">Direct URL Gambar Logo *</label>
                            <input type="url" wire:model.live.debounce.300ms="logoUrl" class="pg-input" placeholder="https://example.com/images/gateway-logo.png">
                            <span style="font-size: 0.6875rem; color: var(--muted); margin-top: 0.25rem; display: block;">Masukkan tautan/URL langsung gambar logo (diawali http:// atau https://).</span>
                        </div>
                    @endif

                    <!-- PREVIEW LOGO LIVE -->
                    <div class="pg-subbox" style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div style="width: 80px; height: 50px; background: #ffffff; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; overflow: hidden; padding: 4px; border: 1px solid var(--border);">
                                @if ($logoType === 'upload' && $logoFile)
                                    <img src="{{ $logoFile->temporaryUrl() }}" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                                @elseif ($logoType === 'url' && !empty($logoUrl))
                                    <img src="{{ $logoUrl }}" style="max-height: 100%; max-width: 100%; object-fit: contain;" onerror="this.style.display='none'">
                                @elseif ($currentLogo)
                                    @if (str_starts_with($currentLogo, 'http://') || str_starts_with($currentLogo, 'https://'))
                                        <img src="{{ $currentLogo }}" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                                    @else
                                        <img src="{{ asset('storage/' . $currentLogo) }}" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                                    @endif
                                @else
                                    <span class="material-symbols-rounded" style="color: var(--muted); font-size: 26px;">image</span>
                                @endif
                            </div>
                            <div>
                                <div style="font-size: 0.8125rem; font-weight: 700; color: var(--text);">Preview Logo Gateway</div>
                                <div style="font-size: 0.6875rem; color: var(--muted);">Logo ini akan tampil pada tabel Payment Gateway & aplikasi pelanggan.</div>
                            </div>
                        </div>
                        @if ($currentLogo || $logoFile || ($logoType === 'url' && $logoUrl))
                            <button type="button" wire:click="deleteLogo" class="pg-btn-secondary" style="color: #ef4444; border-color: rgba(239,68,68,0.3); padding: 0.4rem 0.85rem; font-size: 0.75rem;">
                                <span class="material-symbols-rounded" style="font-size: 16px;">delete</span>
                                <span>Hapus / Reset Logo</span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            <!-- 4. PENGATURAN BIAYA LAYANAN (FEE) -->
            <div class="pg-card">
                <div class="pg-card-header">
                    <h3 class="pg-card-title">
                        <span class="material-symbols-rounded" style="color: #f59e0b;">price_change</span>
                        <span>Pengaturan Biaya Transaksi (Fee)</span>
                    </h3>
                </div>
                <div class="pg-card-body">
                    <div class="pg-grid-2">
                        <div>
                            <label class="pg-label">Tipe Perhitungan Biaya</label>
                            <select wire:model.live="feeType" class="pg-select">
                                <option value="flat">Nominal Tetap (Flat Rp)</option>
                                <option value="percent">Persentase (%)</option>
                            </select>
                            <span style="font-size: 0.6875rem; color: var(--muted); margin-top: 0.25rem; display: block;">Pilih apakah biaya dikenakan tarif tetap (misal Rp 1.500) atau persentase tagihan (misal 1.5%).</span>
                        </div>

                        <div>
                            @if ($feeType === 'flat')
                                <label class="pg-label">Biaya Tetap (Rp)</label>
                                <div style="position: relative;">
                                    <span style="position: absolute; left: 0.875rem; top: 0.65rem; color: var(--muted); font-weight: 700; font-size: 0.875rem;">Rp</span>
                                    <input type="number" wire:model="feeFlat" class="pg-input" style="padding-left: 2.75rem;" min="0" step="100" placeholder="0">
                                </div>
                                <span style="font-size: 0.6875rem; color: var(--muted); margin-top: 0.25rem; display: block;">Isi 0 jika biaya transaksi gratis / 0% MDR ditanggung ISP.</span>
                            @else
                                <label class="pg-label">Persentase Biaya (%)</label>
                                <div style="position: relative;">
                                    <input type="number" wire:model="feePercent" class="pg-input" style="padding-right: 2.5rem;" min="0" max="100" step="0.1" placeholder="1.5">
                                    <span style="position: absolute; right: 0.875rem; top: 0.65rem; color: var(--muted); font-weight: 700; font-size: 0.875rem;">%</span>
                                </div>
                                <span style="font-size: 0.6875rem; color: var(--muted); margin-top: 0.25rem; display: block;">Misal 1.5 untuk membebankan fee 1.5% dari nominal invoice tagihan.</span>
                            @endif
                        </div>
                    </div>

                    <!-- Live Calculation Preview -->
                    <div class="pg-subbox" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem;">
                        <div style="display: flex; align-items: center; gap: 0.6rem;">
                            <span class="material-symbols-rounded" style="color: #0284c7; font-size: 20px;">info</span>
                            <span style="font-size: 0.8125rem; color: var(--muted);">Simulasi untuk tagihan bulanan <strong>Rp 150.000</strong>:</span>
                        </div>
                        <div style="font-size: 0.875rem; font-weight: 800;">
                            @php
                                $sampleAmount = 150000;
                                $calcFee = $feeType === 'percent' ? ($sampleAmount * ($feePercent / 100)) : $feeFlat;
                                $totalSample = $sampleAmount + $calcFee;
                            @endphp
                            <span style="color: var(--muted);">Fee: <strong style="color: #f59e0b;">Rp {{ number_format($calcFee, 0, ',', '.') }}</strong></span> &rarr;
                            <span style="color: var(--text);">Total Bayar Pelanggan: <strong style="color: #059669;">Rp {{ number_format($totalSample, 0, ',', '.') }}</strong></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5. KONFIGURASI KHUSUS & KREDENSIAL SESUAI GATEWAY -->
            <div class="pg-card">
                <div class="pg-card-header">
                    <h3 class="pg-card-title">
                        <span class="material-symbols-rounded" style="color: #0284c7;">vpn_key</span>
                        <span>
                            @if ($code === 'qris_manual')
                                Konfigurasi Barcode QRIS Toko (0% MDR)
                            @elseif ($code === 'manual_transfer')
                                Informasi Rekening Bank Tujuan
                            @else
                                Kredensial API & Secret Key Gateway
                            @endif
                        </span>
                    </h3>
                </div>

                <div class="pg-card-body">
                    @if ($code === 'qris_manual')
                        <!-- QRIS CONFIGURATION -->
                        <div style="background: rgba(0,224,126,0.06); border: 1px solid rgba(0,224,126,0.25); border-radius: 0.75rem; padding: 1.25rem;">
                            <h4 style="font-size: 0.875rem; font-weight: 800; color: #059669; margin: 0 0 0.5rem 0; display: flex; align-items: center; gap: 0.5rem;">
                                <span class="material-symbols-rounded">document_scanner</span>
                                <span>Pindai Foto Barcode QRIS Otomatis</span>
                            </h4>
                            <p style="font-size: 0.75rem; color: var(--muted); margin: 0 0 0.875rem 0;">
                                Pilih metode input barcode QRIS: unggah file gambar langsung atau masukkan direct URL gambar QRIS toko Anda. Sistem akan memindai format EMVCo dan mengisi kode secara otomatis.
                            </p>

                            <!-- Pilihan Sumber Barcode QRIS -->
                            <div style="display: flex; gap: 0.75rem; margin-bottom: 0.875rem; flex-wrap: wrap;">
                                <label style="display: flex; align-items: center; gap: 0.5rem; background: {{ $qrisSourceType === 'upload' ? 'rgba(0,224,126,0.12)' : 'var(--surface2)' }}; border: 1px solid {{ $qrisSourceType === 'upload' ? '#00e07e' : 'var(--border)' }}; padding: 0.45rem 0.85rem; border-radius: 0.5rem; cursor: pointer; color: {{ $qrisSourceType === 'upload' ? '#059669' : 'var(--text)' }}; font-weight: 700; font-size: 0.8125rem;">
                                    <input type="radio" wire:model.live="qrisSourceType" value="upload" style="accent-color: #00e07e;">
                                    <span class="material-symbols-rounded" style="font-size: 16px;">cloud_upload</span>
                                    <span>Upload Langsung</span>
                                </label>

                                <label style="display: flex; align-items: center; gap: 0.5rem; background: {{ $qrisSourceType === 'url' ? 'rgba(0,224,126,0.12)' : 'var(--surface2)' }}; border: 1px solid {{ $qrisSourceType === 'url' ? '#00e07e' : 'var(--border)' }}; padding: 0.45rem 0.85rem; border-radius: 0.5rem; cursor: pointer; color: {{ $qrisSourceType === 'url' ? '#059669' : 'var(--text)' }}; font-weight: 700; font-size: 0.8125rem;">
                                    <input type="radio" wire:model.live="qrisSourceType" value="url" style="accent-color: #00e07e;">
                                    <span class="material-symbols-rounded" style="font-size: 16px;">link</span>
                                    <span>Direct URL</span>
                                </label>
                            </div>

                            <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                                @if ($qrisSourceType === 'upload')
                                    <input type="file" wire:model="qrisImage" accept="image/*" class="pg-input" style="max-width: 320px; padding: 0.4rem;">
                                @else
                                    <input type="url" wire:model="qrisImageUrl" class="pg-input" placeholder="https://example.com/qris-barcode.png" style="max-width: 420px;">
                                @endif

                                <button type="button" wire:click="scanUploadedQris" class="pg-btn-secondary" style="border-color: #00e07e; color: #059669;">
                                    <span class="material-symbols-rounded" style="font-size: 18px;">qr_code_scanner</span>
                                    <span>Scan & Auto-Decode</span>
                                </button>
                                <span wire:loading wire:target="scanUploadedQris" style="font-size: 0.75rem; color: #059669;">Memindai QR code...</span>
                            </div>
                        </div>

                        <div>
                            <label class="pg-label">String Kode QRIS Statis EMVCo *</label>
                            <textarea wire:model="staticQris" rows="4" class="pg-textarea" style="font-family: ui-monospace, monospace; font-size: 0.75rem;" placeholder="00020101021126680016ID.CO.QRIS.WWW..." required></textarea>
                            <span style="font-size: 0.6875rem; color: var(--muted); margin-top: 0.25rem; display: block;">String payload QRIS resmi dari Bank/E-Wallet Anda (dimulai dengan 000201...).</span>
                        </div>

                        <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer; user-select: none;">
                            <input type="checkbox" wire:model="useUniqueCode" style="width: 1.1rem; height: 1.1rem; accent-color: #00e07e; cursor: pointer;">
                            <div>
                                <div style="font-size: 0.8125rem; font-weight: 800; color: var(--text);">Gunakan 3 Digit Kode Unik Tagihan</div>
                                <div style="font-size: 0.6875rem; color: var(--muted);">Menambahkan 3 angka acak pada nominal invoice (misal Rp 150.123) untuk verifikasi mutasi otomatis.</div>
                            </div>
                        </label>

                    @elseif ($code === 'manual_transfer')
                        <!-- MANUAL TRANSFER BANK CONFIGURATION -->
                        <div class="pg-grid-2">
                            <div>
                                <label class="pg-label">Nama Bank Rekening *</label>
                                <input type="text" wire:model="bankName" class="pg-input" placeholder="Contoh: BCA, Mandiri, BRI, BNI" required>
                                <span style="font-size: 0.6875rem; color: var(--muted); margin-top: 0.25rem; display: block;">Nama bank tempat pelanggan mentransfer uang.</span>
                            </div>

                            <div>
                                <label class="pg-label">Nomor Rekening Bank *</label>
                                <input type="text" wire:model="accountNumber" class="pg-input" placeholder="Contoh: 1234567890" required>
                                <span style="font-size: 0.6875rem; color: var(--muted); margin-top: 0.25rem; display: block;">Nomor rekening yang dapat disalin dengan 1 klik oleh pelanggan.</span>
                            </div>
                        </div>

                        <div>
                            <label class="pg-label">Nama Pemilik Rekening (Atas Nama) *</label>
                            <input type="text" wire:model="accountHolder" class="pg-input" placeholder="Contoh: PT NetSpeed Solusi Internet / Nama Pemilik" required>
                            <span style="font-size: 0.6875rem; color: var(--muted); margin-top: 0.25rem; display: block;">Nama resmi pemilik rekening yang tertera di buku tabungan.</span>
                        </div>

                    @else
                        <!-- DIGITAL GATEWAY CONFIGURATION (TRIPAY, DUITKU, MIDTRANS, XENDIT, CEKMUTASI) -->
                        <div class="pg-grid-2">
                            <div>
                                <label class="pg-label">Mode Transaksi</label>
                                <select wire:model="mode" class="pg-select">
                                    <option value="production">Production (Live)</option>
                                    <option value="sandbox">Sandbox (Testing / Uji Coba)</option>
                                </select>
                            </div>

                            <div>
                                <label class="pg-label">Base URL API Endpoint</label>
                                <input type="url" wire:model="apiUrl" class="pg-input" placeholder="https://tripay.co.id/api/ atau https://api.midtrans.com/">
                            </div>
                        </div>

                        <div class="pg-grid-2">
                            <div>
                                <label class="pg-label">Server Key / Private Key / API Key</label>
                                <input type="password" wire:model="serverKey" class="pg-input" placeholder="Private Key dari dashboard gateway">
                            </div>

                            <div>
                                <label class="pg-label">Client Key / Public Key</label>
                                <input type="text" wire:model="clientKey" class="pg-input" placeholder="Public Key dari dashboard gateway">
                            </div>
                        </div>

                        <div class="pg-grid-2">
                            <div>
                                <label class="pg-label">Merchant Code / Project ID / Merchant ID</label>
                                <input type="text" wire:model="merchantCode" class="pg-input" placeholder="Contoh: T12345, D1234, atau ID Merchant">
                            </div>

                            <div>
                                <label class="pg-label">Webhook Secret / Signature Key / Callback Secret</label>
                                <input type="password" wire:model="webhookSecret" class="pg-input" placeholder="Kunci verifikasi signature notifikasi webhook">
                            </div>
                        </div>

                        <!-- Universal Webhook box inside credentials -->
                        <div style="background: var(--surface2); border: 1px solid var(--border); border-radius: 0.75rem; padding: 1rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem;">
                            <div>
                                <div style="font-size: 0.8125rem; font-weight: 800; color: var(--text);">Universal Webhook URL untuk Gateway ini:</div>
                                <code style="font-family: ui-monospace, monospace; font-size: 0.75rem; color: #059669; background: var(--surface); padding: 0.35rem 0.65rem; border-radius: 0.375rem; border: 1px solid var(--border); display: inline-block; margin-top: 0.25rem;">
                                    {{ $webhookUrl }}
                                </code>
                            </div>
                            <button type="button" onclick="navigator.clipboard.writeText('{{ $webhookUrl }}'); alert('Webhook URL berhasil disalin!');" class="pg-btn-secondary" style="padding: 0.45rem 0.85rem; font-size: 0.75rem;">
                                <span class="material-symbols-rounded" style="font-size: 16px;">content_copy</span>
                                <span>Salin URL Webhook</span>
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            <!-- 6. PETUNJUK PEMBAYARAN UNTUK PELANGGAN -->
            <div class="pg-card">
                <div class="pg-card-header">
                    <h3 class="pg-card-title">
                        <span class="material-symbols-rounded" style="color: #00e07e;">help_outline</span>
                        <span>Petunjuk Pembayaran (Ditampilkan ke Pelanggan)</span>
                    </h3>
                </div>
                <div class="pg-card-body">
                    <div>
                        <label class="pg-label">Instruksi Pembayaran</label>
                        <textarea wire:model="instructions" rows="4" class="pg-textarea" placeholder="Tuliskan petunjuk langkah-langkah pembayaran yang akan dibaca oleh pelanggan di aplikasi atau portal..."></textarea>
                        <span style="font-size: 0.6875rem; color: var(--muted); margin-top: 0.25rem; display: block;">Contoh: Buka aplikasi m-Banking Anda, pilih menu Transfer, masukkan nomor rekening di atas, lalu kirim bukti transfer ke CS.</span>
                    </div>
                </div>
            </div>

            <!-- 7. BOTTOM ACTION BUTTONS -->
            <div style="display: flex; align-items: center; justify-content: flex-end; flex-wrap: wrap; gap: 1rem; padding: 0.5rem 0 2rem 0;">
                <a href="{{ \App\Filament\Pages\PaymentGatewaySettings::getUrl() }}" wire:navigate class="pg-btn-secondary">
                    <span class="material-symbols-rounded" style="font-size: 18px;">arrow_back</span>
                    <span>Batal & Kembali</span>
                </a>

                <button type="submit" class="pg-btn-primary" style="padding: 0.75rem 2rem;">
                    <span class="material-symbols-rounded" style="font-size: 20px;">save</span>
                    <span>Simpan</span>
                </button>
            </div>
        </form>
    </div>
</x-filament-panels::page>
