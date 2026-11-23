<x-filament-panels::page>
    <style>
        .pg-container {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            width: 100%;
        }

        .pg-kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1rem;
            width: 100%;
        }

        .pg-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 0.875rem;
            padding: 1.15rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: var(--shadow);
            transition: border-color 0.2s ease;
        }

        .pg-card:hover {
            border-color: #00e07e;
        }

        .pg-card-label {
            font-size: 0.6875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--muted);
        }

        .pg-card-val {
            font-size: 1.625rem;
            font-weight: 800;
            margin-top: 0.25rem;
            font-family: 'Plus Jakarta Sans', ui-monospace, monospace;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text);
        }

        .pg-card-icon {
            width: 44px;
            height: 44px;
            border-radius: 0.625rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .pg-toolbar {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 0.875rem;
            padding: 0.85rem 1.25rem;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            box-shadow: var(--shadow);
        }

        .pg-input {
            padding: 0.5rem 0.85rem;
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 0.5rem;
            font-size: 0.8125rem;
            color: var(--text);
            outline: none;
            transition: border-color 0.2s ease;
        }

        .pg-input:focus {
            border-color: #00e07e;
            background: var(--surface);
        }

        .pg-table-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 0.875rem;
            overflow: hidden;
            width: 100%;
            box-shadow: var(--shadow);
        }

        .pg-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.8125rem;
            text-align: left;
        }

        .pg-th {
            padding: 0.85rem 1rem;
            font-size: 0.6875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--muted);
            background: var(--surface2);
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }

        .pg-td {
            padding: 0.95rem 1rem;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
            color: var(--text);
        }

        .pg-tr:hover {
            background: var(--surface2);
        }

        .pg-badge-active {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 0.25rem 0.65rem;
            border-radius: 0.375rem;
            font-size: 0.6875rem;
            font-weight: 800;
            background: #00e07e;
            color: #ffffff;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .pg-badge-inactive {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 0.25rem 0.65rem;
            border-radius: 0.375rem;
            font-size: 0.6875rem;
            font-weight: 800;
            background: var(--surface2);
            color: var(--muted);
            border: 1px solid var(--border);
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .pg-badge-inactive:hover {
            border-color: #38423e;
            color: var(--text);
        }

        .pg-btn-edit {
            width: 32px;
            height: 32px;
            border-radius: 0.375rem;
            background: var(--surface2);
            border: 1px solid var(--border);
            color: var(--text);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .pg-btn-edit:hover {
            background: var(--border);
            color: #00e07e;
            border-color: #00e07e;
        }

        .pg-modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(4px);
        }

        .pg-modal-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 1rem;
            width: 100%;
            max-width: 640px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.85);
        }

        .pg-logo-box {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 800;
            font-size: 0.9375rem;
        }

        .pg-pulse-dot {
            width: 8px;
            height: 8px;
            border-radius: 9999px;
            display: inline-block;
        }
    </style>

    <div class="pg-container">

        <!-- 1. KPI SUMMARY CARDS -->
        <div class="pg-kpi-grid">
            <div class="pg-card">
                <div>
                    <div class="pg-card-label">Total Payment Gateways</div>
                    <div class="pg-card-val" style="color: var(--text);">{{ count($this->gateways) }}</div>
                    <div style="font-size: 0.6875rem; color: var(--muted); margin-top: 2px;">Tersedia di NetSpeed ISP</div>
                </div>
                <div class="pg-card-icon" style="background: var(--surface2); border: 1px solid var(--border); color: var(--muted);">
                    <span class="material-symbols-rounded" style="font-size: 24px;">account_balance_wallet</span>
                </div>
            </div>

            <div class="pg-card">
                <div>
                    <div class="pg-card-label">Gateway Aktif (Multi-Payment)</div>
                    <div class="pg-card-val" style="color: #00e07e;">
                        <span>{{ \App\Models\PaymentGateway::where('is_active', true)->count() }}</span>
                        <span class="pg-pulse-dot" style="background: #00e07e; animation: pulse 2s infinite;"></span>
                    </div>
                    <div style="font-size: 0.6875rem; color: #00e07e; margin-top: 2px;">Siap digunakan pelanggan</div>
                </div>
                <div class="pg-card-icon" style="background: rgba(0,224,126,0.1); border: 1px solid rgba(0,224,126,0.25); color: #00e07e;">
                    <span class="material-symbols-rounded" style="font-size: 24px;">credit_card</span>
                </div>
            </div>

            <div class="pg-card">
                <div>
                    <div class="pg-card-label">Saluran Bebas Biaya (0% MDR)</div>
                    <div class="pg-card-val" style="color: #38bdf8;">
                        <span>{{ \App\Models\PaymentGateway::where('is_active', true)->where('fee_flat', 0)->where('fee_percent', 0)->count() }}</span>
                    </div>
                    <div style="font-size: 0.6875rem; color: var(--muted); margin-top: 2px;">Bebas potongan per transaksi</div>
                </div>
                <div class="pg-card-icon" style="background: rgba(56,189,248,0.1); border: 1px solid rgba(56,189,248,0.25); color: #38bdf8;">
                    <span class="material-symbols-rounded" style="font-size: 24px;">percent</span>
                </div>
            </div>
        </div>

        <!-- 2. TOOLBAR (SEARCH & FILTER - MIRIP SCREENSHOT) -->
        <div class="pg-toolbar">
            <div style="display: flex; align-items: center; gap: 0.5rem; flex: 1; max-width: 450px;">
                <div style="position: relative; width: 100%;">
                    <span class="material-symbols-rounded" style="position: absolute; left: 10px; top: 9px; font-size: 16px; color: var(--muted);">search</span>
                    <input type="text" wire:model.live.debounce.300ms="searchQuery" placeholder="Search gateway name, code, description..."
                        class="pg-input" style="padding-left: 2rem; width: 100%;">
                </div>
            </div>

            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <select wire:model.live="statusFilter" class="pg-input" style="cursor: pointer; min-width: 130px;">
                    <option value="all">Status: Semua</option>
                    <option value="active">Status: Active</option>
                    <option value="inactive">Status: Inactive</option>
                </select>

                <button type="button" wire:click="resetFilters" class="pg-input" style="cursor: pointer; background: var(--surface2); font-weight: 700;">
                    Reset
                </button>
            </div>
        </div>

        <!-- 3. PAYMENT GATEWAYS TABLE (MIRIP PERSIS SCREENSHOT) -->
        <div class="pg-table-card">
            <div style="overflow-x: auto; width: 100%;">
                <table class="pg-table">
                    <thead>
                        <tr>
                            <th class="pg-th" style="padding-left: 1.25rem; width: 50px;">#</th>
                            <th class="pg-th" style="width: 170px;">Logo</th>
                            <th class="pg-th">Name</th>
                            <th class="pg-th" style="width: 140px;">Fees</th>
                            <th class="pg-th" style="width: 120px;">Status</th>
                            <th class="pg-th" style="padding-right: 1.25rem; width: 80px; text-align: center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->gateways as $idx => $gw)
                            <tr class="pg-tr">
                                <!-- # Sort Handle -->
                                <td class="pg-td" style="padding-left: 1.25rem;">
                                    <span class="material-symbols-rounded" style="font-size: 18px; color: var(--muted); opacity: 0.6;">drag_indicator</span>
                                </td>

                                <!-- Logo -->
                                <td class="pg-td">
                                    <div class="pg-logo-box">
                                        @if ($gw->logo_url)
                                            <div style="background: #ffffff; padding: 3px 6px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; min-width: 55px; height: 32px; border: 1px solid var(--border);">
                                                <img src="{{ $gw->logo_url }}" alt="{{ $gw->name }}" style="max-height: 24px; max-width: 80px; object-fit: contain;">
                                            </div>
                                        @elseif ($gw->code === 'qris_manual')
                                            <span style="background: #e11d48; color: #ffffff; padding: 0.25rem 0.55rem; border-radius: 4px; font-size: 0.75rem; font-weight: 900; letter-spacing: 0.05em;">
                                                QRIS
                                            </span>
                                        @elseif ($gw->code === 'tripay')
                                            <span style="background: #0284c7; color: #ffffff; padding: 0.25rem 0.55rem; border-radius: 4px; font-size: 0.75rem; font-weight: 900; letter-spacing: 0.05em;">
                                                TriPay
                                            </span>
                                        @elseif ($gw->code === 'duitku')
                                            <span style="background: #ea580c; color: #ffffff; padding: 0.25rem 0.55rem; border-radius: 4px; font-size: 0.75rem; font-weight: 900; letter-spacing: 0.05em;">
                                                duitku
                                            </span>
                                        @elseif ($gw->code === 'midtrans')
                                            <span style="background: #1e3a8a; color: #ffffff; padding: 0.25rem 0.55rem; border-radius: 4px; font-size: 0.75rem; font-weight: 900; letter-spacing: 0.05em;">
                                                midtrans
                                            </span>
                                        @elseif ($gw->code === 'xendit')
                                            <span style="background: #2563eb; color: #ffffff; padding: 0.25rem 0.55rem; border-radius: 4px; font-size: 0.75rem; font-weight: 900; letter-spacing: 0.05em;">
                                                xendit
                                            </span>
                                        @elseif ($gw->code === 'cekmutasi')
                                            <span style="background: #059669; color: #ffffff; padding: 0.25rem 0.55rem; border-radius: 4px; font-size: 0.75rem; font-weight: 900; letter-spacing: 0.05em;">
                                                CekMutasi
                                            </span>
                                        @else
                                            <span style="background: #4f46e5; color: #ffffff; padding: 0.25rem 0.55rem; border-radius: 4px; font-size: 0.75rem; font-weight: 900; letter-spacing: 0.05em;">
                                                BANK
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Name & Description -->
                                <td class="pg-td">
                                    <div style="font-weight: 700; color: var(--text); font-size: 0.875rem;">{{ $gw->name }}</div>
                                    <div style="font-size: 0.6875rem; color: var(--muted); margin-top: 2px;">{{ $gw->description }}</div>
                                </td>

                                <!-- Fees -->
                                <td class="pg-td" style="font-family: ui-monospace, monospace; font-size: 0.8125rem; font-weight: 700; color: var(--text);">
                                    @if ($gw->fee_formatted === '0% (Gratis)')
                                        <span style="color: #00e07e;">- (Gratis)</span>
                                    @else
                                        <span style="color: #f2c56b;">{{ $gw->fee_formatted }}</span>
                                    @endif
                                </td>

                                <!-- Status (Toggleable Livewire Button) -->
                                <td class="pg-td">
                                    <button type="button" wire:click="toggleGateway({{ $gw->id }})" title="Klik untuk mengubah status">
                                        @if ($gw->is_active)
                                            <span class="pg-badge-active">
                                                <span>Active</span>
                                                <span class="pg-pulse-dot" style="background: #06261a;"></span>
                                            </span>
                                        @else
                                            <span class="pg-badge-inactive">
                                                <span>Inactive</span>
                                            </span>
                                        @endif
                                    </button>
                                </td>

                                <!-- Action (Edit Page Button) -->
                                <td class="pg-td" style="padding-right: 1.25rem; text-align: center;">
                                    <a href="{{ \App\Filament\Pages\EditPaymentGateway::getUrl(['record' => $gw->id]) }}" wire:navigate class="pg-btn-edit" title="Edit Gateway Configuration">
                                        <span class="material-symbols-rounded" style="font-size: 16px;">edit_square</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="padding: 3rem 1.5rem; text-align: center; color: #64748b;">
                                    <span class="material-symbols-rounded" style="font-size: 36px; opacity: 0.4; display: block; margin: 0 auto 0.5rem auto;">credit_card_off</span>
                                    <div>Tidak ada gateway pembayaran yang sesuai filter pencarian.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 4. GLOBAL WEBHOOK & AUTOMATION BOX -->
        <div style="background: var(--surface); border: 1px solid var(--border); border-radius: 0.875rem; padding: 1.25rem; display: flex; flex-direction: column; gap: 1rem;">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem;">
                <div style="display: flex; align-items: center; gap: 0.6rem;">
                    <div style="width: 36px; height: 36px; border-radius: 0.5rem; background: rgba(56,189,248,0.12); border: 1px solid rgba(56,189,248,0.25); display: flex; align-items: center; justify-content: center; color: #38bdf8;">
                        <span class="material-symbols-rounded" style="font-size: 20px;">webhook</span>
                    </div>
                    <div>
                        <h4 style="font-weight: 700; font-size: 0.875rem; color: var(--text); margin: 0;">Universal Webhook Callback URL</h4>
                        <p style="font-size: 0.6875rem; color: var(--muted); margin: 2px 0 0 0;">Salin URL ini ke dashboard gateway Anda (TriPay, Midtrans, CekMutasi, Moota, Xendit) untuk pelunasan instan</p>
                    </div>
                </div>

                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <code style="font-family: ui-monospace, monospace; font-size: 0.75rem; color: #00e07e; background: var(--surface2); padding: 0.4rem 0.75rem; border-radius: 0.375rem; border: 1px solid var(--border);">
                        {{ $webhookUrl }}
                    </code>
                    <button type="button" onclick="navigator.clipboard.writeText('{{ $webhookUrl }}'); alert('Webhook URL berhasil disalin!');"
                        class="pg-input" style="cursor: pointer; background: var(--surface2); font-weight: 700; color: #38bdf8;">
                        Salin URL
                    </button>
                </div>
            </div>

            <div style="border-top: 1px solid var(--border); padding-top: 1rem; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem;">
                <div style="display: flex; align-items: center; gap: 1.5rem; flex-wrap: wrap;">
                    <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.75rem; color: var(--text); cursor: pointer;">
                        <input type="checkbox" wire:model="autoActivate" style="accent-color: #00e07e; width: 15px; height: 15px;">
                        <span>Auto-Unblock / Buka Isolir PPPoE</span>
                    </label>

                    <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.75rem; color: var(--text); cursor: pointer;">
                        <input type="checkbox" wire:model="autoReceipt" style="accent-color: #00e07e; width: 15px; height: 15px;">
                        <span>Auto-Kirim Kwitansi WhatsApp</span>
                    </label>

                    <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.75rem; color: var(--text); cursor: pointer;">
                        <input type="checkbox" wire:model="autoTelegram" style="accent-color: #00e07e; width: 15px; height: 15px;">
                        <span>Alert Telegram NOC Lunas</span>
                    </label>
                </div>

                <button type="button" wire:click="saveGlobalAutomation"
                    class="pg-input" style="background: #00e07e; color: #ffffff; font-weight: 800; border: none; cursor: pointer; padding: 0.45rem 1rem;">
                    Simpan
                </button>
            </div>
        </div>
    </div>
</x-filament-panels::page>
