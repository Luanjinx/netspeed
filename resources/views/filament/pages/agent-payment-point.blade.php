<x-filament-panels::page>
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">

        <!-- 1. Header Card: Agent Profile & Balance Overview -->
        <div class="fi-section" style="padding: 1.25rem 1.5rem; background: var(--surface); border: 1px solid var(--border); border-radius: 1rem;">
            <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1.25rem;">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="width: 44px; height: 44px; border-radius: 0.75rem; background: rgba(0, 224, 126, 0.12); border: 1px solid rgba(0, 224, 126, 0.3); display: flex; align-items: center; justify-content: center; color: #00e07e; flex-shrink: 0;">
                        <span class="material-symbols-rounded" style="font-size: 24px;">storefront</span>
                    </div>
                    <div>
                        <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                            <h2 style="font-size: 1.1rem; font-weight: 800; color: var(--text); margin: 0;">
                                {{ $this->activeAgent?->name ?? 'Pilih Akun Agen' }}
                            </h2>
                            @if ($this->activeAgent)
                                <span class="noc-pill noc-pill-primary" style="font-size: 0.6875rem;">
                                    {{ $this->activeAgent->agent_code }}
                                </span>
                            @endif
                        </div>
                        <p style="font-size: 0.75rem; color: var(--muted); margin-top: 0.2rem;">
                            {{ $this->activeAgent?->address ?: 'Loket resmi penerimaan pembayaran tagihan internet pelanggan' }}
                        </p>
                    </div>
                </div>

                <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
                    @if (auth()->user()?->role === 'admin')
                        <!-- Admin Selector -->
                        <div style="display: flex; flex-direction: column; gap: 4px;">
                            <label style="font-size: 0.6875rem; font-weight: 700; color: var(--muted); text-transform: uppercase;">Pilih Agen / Loket:</label>
                            <select wire:model.live="selectedAgentId"
                                    class="ont-input"
                                    style="height: 38px; padding: 0 0.75rem; font-size: 0.8125rem; font-weight: 700; background: var(--surface2); color: var(--text); border: 1px solid var(--border); border-radius: 0.5rem; outline: none; cursor: pointer;">
                                @foreach (\App\Models\Agent::where('is_active', true)->get() as $agent)
                                    <option value="{{ $agent->id }}">
                                        {{ $agent->agent_code }} - {{ $agent->name }} (Saldo: {{ $agent->formatted_balance }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if ($this->activeAgent)
                        <!-- Saldo Deposit Card -->
                        <div style="background: var(--surface2); border: 1px solid var(--border); border-radius: 0.75rem; padding: 0.65rem 1.15rem; display: flex; align-items: center; gap: 1rem;">
                            <div>
                                <div style="font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; color: var(--muted);">Saldo Deposit:</div>
                                <div style="font-size: 1.25rem; font-weight: 900; color: {{ (float) $this->activeAgent->balance > 0 ? '#00e07e' : '#f27b7b' }}; line-height: 1.2;">
                                    {{ $this->activeAgent->formatted_balance }}
                                </div>
                            </div>
                            <div style="border-left: 1px solid var(--border); padding-left: 0.85rem; font-size: 0.6875rem; color: var(--muted);">
                                Komisi per tagihan:<br>
                                <strong style="color: #00e07e; font-size: 0.8125rem;">Rp {{ number_format((float) $this->activeAgent->commission_per_transaction, 0, ',', '.') }}</strong>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- 2. Universal Search Box (Invoice / PPPoE / Phone / Name / Barcode) -->
        <div class="fi-section" style="padding: 1.5rem; background: var(--surface); border: 1px solid var(--border); border-radius: 1rem;">
            <div style="margin-bottom: 1rem;">
                <h3 style="font-size: 0.95rem; font-weight: 800; color: var(--text); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <span class="material-symbols-rounded" style="color: #00e07e; font-size: 20px;">search</span>
                    <span>Pencarian Tagihan Fleksibel (Universal Search)</span>
                </h3>
                <p style="font-size: 0.75rem; color: var(--muted); margin-top: 0.25rem;">
                    Ketik salah satu data: <strong>Nomor Tagihan (INV-...)</strong>, <strong>Username PPPoE</strong>, <strong>No Pelanggan</strong>, <strong>No WhatsApp</strong>, atau <strong>Nama Pelanggan</strong>.
                </p>
            </div>

            <!-- Big Input Search with Instant Debounce -->
            <div style="position: relative; width: 100%;">
                <input type="text"
                       wire:model.live.debounce.300ms="searchQuery"
                       placeholder="Ketik Nomor Tagihan, Username PPPoE, No WhatsApp, atau Nama Pelanggan..."
                       autofocus
                       style="width: 100%; height: 50px; padding: 0 1rem 0 3rem; font-size: 0.95rem; font-weight: 600; border-radius: 0.75rem; background: var(--surface2); border: 2px solid @if (!empty($searchResults)) #00e07e @else var(--border) @endif; color: var(--text); outline: none; transition: all 0.2s ease;">
                <span class="material-symbols-rounded" style="position: absolute; left: 1rem; top: 15px; font-size: 20px; color: #00e07e;">
                    qr_code_scanner
                </span>
                @if (strlen($searchQuery) > 0)
                    <button type="button"
                            wire:click="$set('searchQuery', ''); $set('searchResults', []);"
                            style="position: absolute; right: 1rem; top: 15px; background: transparent; border: none; color: var(--muted); font-size: 1.1rem; cursor: pointer;">
                        ✕
                    </button>
                @endif
            </div>

            <!-- Search Results Dropdown List -->
            @if (!empty($searchResults) && empty($selectedInvoice))
                <div style="margin-top: 1rem; display: flex; flex-direction: column; gap: 0.65rem;">
                    <div style="font-size: 0.75rem; font-weight: 700; color: var(--muted); text-transform: uppercase;">
                        Ditemukan {{ count($searchResults) }} Tagihan Belum Lunas:
                    </div>
                    @foreach ($searchResults as $item)
                        <div wire:click="selectInvoice({{ $item['id'] }})"
                             style="cursor: pointer; padding: 1rem 1.25rem; background: var(--surface2); border: 1px solid var(--border); border-radius: 0.75rem; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; transition: all 0.2s ease;"
                             onmouseover="this.style.borderColor='#00e07e'; this.style.transform='translateY(-1px)';"
                             onmouseout="this.style.borderColor='var(--border)'; this.style.transform='none';">
                            <div style="display: flex; align-items: center; gap: 0.85rem;">
                                <div style="width: 38px; height: 38px; border-radius: 0.5rem; background: rgba(0, 224, 126, 0.1); border: 1px solid rgba(0, 224, 126, 0.25); display: flex; align-items: center; justify-content: center; color: #00e07e; flex-shrink: 0;">
                                    <span class="material-symbols-rounded" style="font-size: 20px;">receipt_long</span>
                                </div>
                                <div>
                                    <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                                        <span style="font-weight: 800; font-size: 0.9rem; color: var(--text);">{{ $item['customer_name'] }}</span>
                                        <span class="noc-pill noc-pill-primary" style="font-size: 0.6875rem;">PPPoE: {{ $item['pppoe_username'] }}</span>
                                        @if ($item['is_isolated'])
                                            <span class="noc-pill noc-pill-danger" style="font-size: 0.6875rem;">TERISOLIR</span>
                                        @endif
                                    </div>
                                    <div style="font-size: 0.75rem; color: var(--muted); margin-top: 0.2rem;">
                                        Inv: <strong>{{ $item['invoice_number'] }}</strong> &bull; Paket: {{ $item['package_name'] }} &bull; Periode: {{ $item['period'] }}
                                    </div>
                                </div>
                            </div>

                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <div style="text-align: right;">
                                    <div style="font-size: 1.1rem; font-weight: 900; color: #00e07e;">
                                        Rp {{ number_format($item['total_amount'], 0, ',', '.') }}
                                    </div>
                                    <div style="font-size: 0.6875rem; color: var(--muted);">Jatuh Tempo: {{ $item['due_date'] }}</div>
                                </div>
                                <button type="button"
                                        class="noc-btn noc-btn-primary"
                                        style="height: 36px; padding: 0 0.85rem; font-size: 0.75rem; font-weight: 800; border-radius: 0.5rem;">
                                    Pilih &amp; Bayar
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @elseif (strlen($searchQuery) >= 2 && empty($searchResults) && empty($selectedInvoice))
                <div style="margin-top: 1.25rem; text-align: center; padding: 2rem; background: var(--surface2); border: 1px dashed var(--border); border-radius: 0.75rem; color: var(--muted); font-size: 0.8125rem;">
                    <span class="material-symbols-rounded" style="font-size: 32px; display: block; margin: 0 auto 0.5rem auto; opacity: 0.4;">search_off</span>
                    Tidak ditemukan tagihan tertunggak untuk pencarian <strong>"{{ $searchQuery }}"</strong>. Pastikan invoice belum lunas.
                </div>
            @endif

            <!-- 3. Selected Invoice Detail & Payment Confirmation -->
            @if ($selectedInvoice)
                <div style="margin-top: 1.5rem; padding: 1.5rem; background: var(--surface2); border: 2px solid #00e07e; border-radius: 1rem;">
                    <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; border-bottom: 1px solid var(--border); padding-bottom: 1rem; margin-bottom: 1.25rem;">
                        <div>
                            <span class="noc-pill noc-pill-primary" style="font-size: 0.6875rem;">Konfirmasi Pembayaran Tagihan</span>
                            <h3 style="font-size: 1.2rem; font-weight: 900; color: var(--text); margin: 0.35rem 0 0 0;">
                                {{ $selectedInvoice['invoice_number'] }}
                            </h3>
                        </div>
                        <button type="button"
                                wire:click="cancelSelectedInvoice"
                                class="noc-btn noc-btn-secondary"
                                style="font-size: 0.75rem;">
                            ✕ Batalkan Pilihan
                        </button>
                    </div>

                    <!-- Information Grid -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.25rem;">
                        <div style="background: var(--surface); padding: 0.85rem; border-radius: 0.625rem; border: 1px solid var(--border);">
                            <div style="font-size: 0.6875rem; color: var(--muted); text-transform: uppercase;">Pelanggan &amp; Akun PPPoE</div>
                            <div style="font-weight: 800; font-size: 0.9rem; color: var(--text); margin-top: 3px;">{{ $selectedInvoice['customer_name'] }}</div>
                            <div style="font-size: 0.75rem; color: #00e07e; font-family: monospace; font-weight: 700; margin-top: 2px;">{{ $selectedInvoice['pppoe_username'] }}</div>
                            <div style="font-size: 0.6875rem; color: var(--muted); margin-top: 2px;">No. Pelanggan: {{ $selectedInvoice['customer_number'] }}</div>
                        </div>

                        <div style="background: var(--surface); padding: 0.85rem; border-radius: 0.625rem; border: 1px solid var(--border);">
                            <div style="font-size: 0.6875rem; color: var(--muted); text-transform: uppercase;">Paket &amp; Periode</div>
                            <div style="font-weight: 800; font-size: 0.9rem; color: var(--text); margin-top: 3px;">{{ $selectedInvoice['package_name'] }}</div>
                            <div style="font-size: 0.75rem; color: var(--muted); margin-top: 2px;">Bulan: <strong>{{ $selectedInvoice['period'] }}</strong></div>
                            <div style="font-size: 0.6875rem; color: var(--muted); margin-top: 2px;">Jatuh Tempo: {{ $selectedInvoice['due_date'] }}</div>
                        </div>

                        <div style="background: var(--surface); padding: 0.85rem; border-radius: 0.625rem; border: 1px solid var(--border);">
                            <div style="font-size: 0.6875rem; color: var(--muted); text-transform: uppercase;">Status Jaringan</div>
                            @if ($selectedInvoice['is_isolated'])
                                <div style="display: flex; align-items: center; gap: 6px; margin-top: 4px;">
                                    <span class="noc-dot noc-dot-danger"></span>
                                    <strong style="color: #f27b7b; font-size: 0.85rem;">TERISOLIR</strong>
                                </div>
                                <div style="font-size: 0.6875rem; color: #00e07e; margin-top: 4px;">
                                    ⚡ Internet otomatis aktif &amp; normal detik ini juga setelah dibayar!
                                </div>
                            @else
                                <div style="display: flex; align-items: center; gap: 6px; margin-top: 4px;">
                                    <span class="noc-dot noc-dot-success"></span>
                                    <strong style="color: #00e07e; font-size: 0.85rem;">AKTIF / NORMAL</strong>
                                </div>
                                <div style="font-size: 0.6875rem; color: var(--muted); margin-top: 4px;">
                                    Pembayaran rutin tepat waktu
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Payment Calculations & CTA -->
                    @php
                        $fee = (float) ($this->activeAgent?->commission_per_transaction ?? 2000);
                        $invoiceTotal = (float) $selectedInvoice['total_amount'];
                        $netDeduct = max(0, $invoiceTotal - $fee);
                        $agentBal = (float) ($this->activeAgent?->balance ?? 0);
                        $isSufficient = $agentBal >= $netDeduct;
                    @endphp

                    <div style="padding: 1.25rem; background: var(--surface); border: 1px solid var(--border); border-radius: 0.75rem; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1.25rem;">
                        <div style="display: flex; gap: 2rem; flex-wrap: wrap;">
                            <div>
                                <div style="font-size: 0.6875rem; color: var(--muted); text-transform: uppercase;">Total Uang dari Pelanggan:</div>
                                <div style="font-size: 1.4rem; font-weight: 900; color: #00e07e;">
                                    Rp {{ number_format($invoiceTotal, 0, ',', '.') }}
                                </div>
                            </div>
                            <div>
                                <div style="font-size: 0.6875rem; color: var(--muted); text-transform: uppercase;">Komisi Agen (Hak Anda):</div>
                                <div style="font-size: 1.1rem; font-weight: 800; color: var(--text);">
                                    Rp {{ number_format($fee, 0, ',', '.') }}
                                </div>
                            </div>
                            <div>
                                <div style="font-size: 0.6875rem; color: var(--muted); text-transform: uppercase;">Saldo Agen Terpotong:</div>
                                <div style="font-size: 1.25rem; font-weight: 900; color: #38bdf8;">
                                    Rp {{ number_format($netDeduct, 0, ',', '.') }}
                                </div>
                            </div>
                        </div>

                        <div>
                            @if ($isSufficient)
                                <button type="button"
                                        wire:click="processPayment"
                                        wire:loading.attr="disabled"
                                        class="noc-btn noc-btn-primary"
                                        style="height: 48px; padding: 0 1.75rem; font-size: 0.9375rem; font-weight: 800; border-radius: 0.625rem; display: inline-flex; align-items: center; gap: 8px;">
                                    <span wire:loading.remove class="material-symbols-rounded" style="font-size: 20px;">payments</span>
                                    <span wire:loading class="material-symbols-rounded" style="font-size: 20px; animation: spin 1s linear infinite;">sync</span>
                                    <span wire:loading.remove>Bayar Sekarang (Potong Saldo)</span>
                                    <span wire:loading>Memproses Transaksi...</span>
                                </button>
                            @else
                                <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 4px;">
                                    <button type="button"
                                            disabled
                                            style="height: 46px; padding: 0 1.5rem; font-size: 0.875rem; font-weight: 800; border-radius: 0.625rem; background: rgba(242, 123, 123, 0.2); color: #f27b7b; border: 1px solid rgba(242, 123, 123, 0.4); cursor: not-allowed; display: inline-flex; align-items: center; gap: 6px;">
                                        <span class="material-symbols-rounded" style="font-size: 18px;">error</span>
                                        <span>Saldo Tidak Cukup (Kurang Rp {{ number_format($netDeduct - $agentBal, 0, ',', '.') }})</span>
                                    </button>
                                    <span style="font-size: 0.6875rem; color: var(--muted);">Silakan minta admin untuk Top-Up saldo deposit Anda.</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- 4. Thermal Receipt Modal (58mm / 80mm POS Printer Format) -->
        @if ($lastSuccessTransaction)
            <div style="position: fixed; inset: 0; background: rgba(0, 0, 0, 0.75); backdrop-filter: blur(4px); z-index: 99999; display: flex; align-items: center; justify-content: center; padding: 1rem;">
                <div style="background: var(--surface); border: 1px solid var(--border); border-radius: 1rem; width: 100%; max-width: 440px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7); overflow: hidden; display: flex; flex-direction: column;">
                    <!-- Modal Header -->
                    <div style="padding: 1rem 1.25rem; border-bottom: 1px solid var(--border); background: var(--surface2); display: flex; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center; gap: 6px; color: #00e07e; font-weight: 800; font-size: 0.9375rem;">
                            <span class="material-symbols-rounded">check_circle</span>
                            <span>Transaksi Lunas Berhasil</span>
                        </div>
                        <button type="button" wire:click="closeReceiptModal" style="background: transparent; border: none; color: var(--muted); font-size: 1.25rem; cursor: pointer;">✕</button>
                    </div>

                    <!-- Printable Receipt Area (Class .receipt-paper) -->
                    <div id="thermal-receipt-area" class="receipt-paper" style="padding: 1.5rem; background: #ffffff; color: #000000; font-family: 'Courier New', Courier, monospace; font-size: 12px; line-height: 1.4;">
                        <div style="text-align: center; border-bottom: 1px dashed #000000; padding-bottom: 0.75rem; margin-bottom: 0.75rem;">
                            <div style="font-size: 16px; font-weight: 900; letter-spacing: 0.05em;">NETSPEED BROADBAND</div>
                            <div style="font-size: 11px;">BUKTI PEMBAYARAN INTERNET</div>
                            <div style="font-size: 10px; margin-top: 2px;">Loket: {{ $lastSuccessTransaction['agent_name'] }} ({{ $lastSuccessTransaction['agent_code'] }})</div>
                        </div>

                        <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                            <span>No. Ref:</span>
                            <strong>{{ $lastSuccessTransaction['reference'] }}</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                            <span>Waktu:</span>
                            <span>{{ $lastSuccessTransaction['time'] }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                            <span>Invoice:</span>
                            <strong>{{ $lastSuccessTransaction['invoice_number'] }}</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                            <span>Periode:</span>
                            <span>{{ $lastSuccessTransaction['period'] }}</span>
                        </div>

                        <div style="border-top: 1px dashed #000000; margin: 0.5rem 0; padding-top: 0.5rem;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                <span>Pelanggan:</span>
                                <strong>{{ $lastSuccessTransaction['customer_name'] }}</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                <span>ID/No Pel:</span>
                                <span>{{ $lastSuccessTransaction['customer_number'] }}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                <span>Akun PPPoE:</span>
                                <strong>{{ $lastSuccessTransaction['pppoe_username'] }}</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                <span>Paket:</span>
                                <span>{{ $lastSuccessTransaction['package_name'] }}</span>
                            </div>
                        </div>

                        <div style="border-top: 1px dashed #000000; margin: 0.5rem 0; padding-top: 0.5rem;">
                            <div style="display: flex; justify-content: space-between; font-size: 14px; font-weight: 900; margin-bottom: 4px;">
                                <span>TOTAL BAYAR:</span>
                                <span>Rp {{ number_format($lastSuccessTransaction['amount_paid'], 0, ',', '.') }}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; font-size: 11px;">
                                <span>STATUS:</span>
                                <strong>LUNAS (PAID)</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; font-size: 11px;">
                                <span>INTERNET:</span>
                                <strong>AKTIF / NORMAL</strong>
                            </div>
                        </div>

                        <div style="text-align: center; border-top: 1px dashed #000000; padding-top: 0.75rem; margin-top: 0.75rem; font-size: 10px;">
                            <div>Terima kasih atas pembayaran Anda.</div>
                            <div>Simpan struk ini sebagai bukti pembayaran sah.</div>
                            <div style="margin-top: 4px; font-weight: bold;">Layanan Pelanggan: NetSpeed Care</div>
                        </div>
                    </div>

                    <!-- Modal Action Buttons -->
                    <div style="padding: 1rem 1.25rem; border-top: 1px solid var(--border); background: var(--surface2); display: flex; gap: 0.75rem; justify-content: flex-end;">
                        <button type="button"
                                onclick="window.print()"
                                class="noc-btn noc-btn-primary"
                                style="height: 40px; padding: 0 1.25rem; font-size: 0.8125rem; font-weight: 800; border-radius: 0.5rem; display: inline-flex; align-items: center; gap: 6px;">
                            <span class="material-symbols-rounded" style="font-size: 18px;">print</span>
                            <span>Cetak Struk Thermal (Print)</span>
                        </button>
                        <button type="button"
                                wire:click="closeReceiptModal"
                                class="noc-btn noc-btn-secondary"
                                style="height: 40px; padding: 0 1rem; font-size: 0.8125rem; border-radius: 0.5rem;">
                            Transaksi Baru
                        </button>
                    </div>
                </div>
            </div>
        @endif

        <!-- 5. Riwayat 5 Transaksi Terakhir Agen -->
        @if ($this->activeAgent && $this->recentTransactions->isNotEmpty())
            <div class="fi-section" style="padding: 1.25rem 1.5rem; background: var(--surface); border: 1px solid var(--border); border-radius: 1rem;">
                <div style="margin-bottom: 0.85rem; display: flex; align-items: center; justify-content: space-between;">
                    <h3 style="font-size: 0.875rem; font-weight: 800; color: var(--text); margin: 0; display: flex; align-items: center; gap: 6px;">
                        <span class="material-symbols-rounded" style="font-size: 18px; color: #818cf8;">history</span>
                        <span>Riwayat Transaksi Terakhir Agen ({{ $this->activeAgent->name }})</span>
                    </h3>
                </div>

                <div style="overflow-x: auto;">
                    <table class="isp-table-clean">
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>Referensi</th>
                                <th>Keterangan</th>
                                <th style="text-align: right;">Total Transaksi</th>
                                <th style="text-align: right;">Komisi Agen</th>
                                <th style="text-align: right;">Sisa Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->recentTransactions as $t)
                                <tr>
                                    <td style="font-size: 0.75rem; color: var(--muted); white-space: nowrap;">
                                        {{ $t->created_at->format('d/m/Y H:i') }}
                                    </td>
                                    <td style="font-family: monospace; font-weight: 700; font-size: 0.75rem;">
                                        {{ $t->payment_reference ?: '-' }}
                                    </td>
                                    <td style="font-size: 0.8125rem; font-weight: 600;">
                                        {{ $t->description }}
                                    </td>
                                    <td style="font-weight: 800; text-align: right; color: {{ in_array($t->type, ['deposit', 'refund']) ? '#00e07e' : '#38bdf8' }};">
                                        {{ in_array($t->type, ['deposit', 'refund']) ? '+' : '' }} Rp {{ number_format((float) $t->amount, 0, ',', '.') }}
                                    </td>
                                    <td style="text-align: right; color: #00e07e; font-weight: 700;">
                                        {{ (float) $t->fee > 0 ? '+ Rp ' . number_format((float) $t->fee, 0, ',', '.') : '-' }}
                                    </td>
                                    <td style="text-align: right; font-weight: 700; color: var(--text);">
                                        Rp {{ number_format((float) $t->balance_after, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

    </div>

    <!-- Thermal Print CSS Engine -->
    <style>
        @media print {
            body * {
                visibility: hidden !important;
            }
            #thermal-receipt-area, #thermal-receipt-area * {
                visibility: visible !important;
            }
            #thermal-receipt-area {
                position: absolute !important;
                left: 0 !important;
                top: 0 !important;
                width: 58mm !important;
                max-width: 80mm !important;
                padding: 2mm !important;
                margin: 0 !important;
                background: #ffffff !important;
                color: #000000 !important;
                box-shadow: none !important;
                border: none !important;
            }
            @page {
                size: auto;
                margin: 2mm;
            }
        }
    </style>
</x-filament-panels::page>

