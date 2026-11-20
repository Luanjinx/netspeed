<div class="fi-wi-widget col-span-full fi-wi-network-health" style="grid-column: 1 / -1 !important; width: 100% !important;">
    <x-filament::section>
        <div class="noc-wrapper">
            <!-- Header Widget -->
            <div class="noc-header-bar">
                <div class="noc-title-group" style="display: flex; align-items: center; gap: 14px;">
                    <img src="{{ asset('images/mascot-technician.webp') }}" alt="Mascot NOC" style="width: 44px; height: auto; filter: drop-shadow(0 4px 10px rgba(0,224,126,0.3)); flex-shrink: 0;">
                    <div>
                        <h3 class="noc-title">
                            Pusat Diagnostik &amp; Pemantauan Gangguan Jaringan (NOC Overview)
                        </h3>
                        <p class="noc-subtitle">
                            Deteksi dini kabel putus, status gateway MikroTik, dan pemantauan ketersediaan layanan.
                        </p>
                    </div>
                </div>

                <div class="noc-actions-group">
                    <a href="{{ url('/admin/router-monitor') }}" class="noc-btn noc-btn-primary">
                        <svg style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                        <span>Monitor Trafik Router</span>
                    </a>
                    <a href="{{ url('/admin/network-map') }}" class="noc-btn noc-btn-secondary">
                        <svg style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" /></svg>
                        <span>Peta Jaringan</span>
                    </a>
                </div>
            </div>

            <!-- 3 Grid Sections -->
            <div class="noc-grid noc-grid-3">
                {{-- Col 1: Server & Gateway Health --}}
                <div class="noc-subcard">
                    <div class="noc-card-header">
                        <span class="noc-dot noc-dot-primary"></span>
                        <span>Koneksi Core &amp; Server</span>
                    </div>

                    <div class="noc-row">
                        <span class="noc-label">Router MikroTik:</span>
                        @if ($offline_routers === 0 && $total_routers > 0)
                            <span class="noc-pill noc-pill-success">
                                {{ $online_routers }}/{{ $total_routers }} Normal (Online)
                            </span>
                        @else
                            <span class="noc-pill noc-pill-danger">
                                {{ $offline_routers }} Router Offline
                            </span>
                        @endif
                    </div>

                    <div class="noc-row">
                        <span class="noc-label">Server TR-069:</span>
                        @if ($genieacs_online)
                            <span class="noc-pill noc-pill-success">
                                Online
                            </span>
                        @else
                            <span class="noc-pill noc-pill-warning">
                                Standby / Offline
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Col 2: Outage & Fiber Cut Detector --}}
                <div class="noc-subcard @if (count($potential_outages) > 0) noc-subcard-alert @else noc-subcard-success @endif">
                    <div class="noc-card-header" style="@if (count($potential_outages) > 0) color: #f27b7b; @else color: #00e07e; @endif">
                        @if (count($potential_outages) > 0)
                            <span class="noc-dot noc-dot-danger"></span>
                            <span>⚠️ Peringatan Potensi Kabel Putus</span>
                        @else
                            <span class="noc-dot noc-dot-success"></span>
                            <span>Jalur Fiber ODP Normal</span>
                        @endif
                    </div>

                    @if (count($potential_outages) > 0)
                        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                            @foreach ($potential_outages as $outage)
                                <div class="noc-outage-box">
                                    <div style="display: flex; justify-content: space-between; align-items: center; font-weight: 700; color: #f27b7b; font-size: 0.75rem;">
                                        <span>{{ $outage['odp_name'] }}</span>
                                        <span class="noc-pill noc-pill-danger" style="font-size: 0.65rem; padding: 0.1rem 0.4rem;">
                                            {{ $outage['offline_cust'] }}/{{ $outage['total_cust'] }} Pelanggan Offline
                                        </span>
                                    </div>
                                    <div style="font-size: 0.6875rem; color: #9aa49f; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                                        {{ $outage['address'] }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p style="color: #00e07e; font-size: 0.75rem; margin: 0; line-height: 1.4;">
                            Seluruh titik ODP beroperasi normal. Tidak ada indikasi pemutusan kabel feeder atau gangguan massal di lapangan.
                        </p>
                    @endif
                </div>

                {{-- Col 3: Customer Availability Rate --}}
                <div class="noc-subcard">
                    <div class="noc-card-header">
                        <span class="noc-dot noc-dot-success"></span>
                        <span>Kesehatan Layanan Pelanggan</span>
                    </div>

                    <div class="noc-row">
                        <span class="noc-label">Tingkat Layanan Aktif:</span>
                        <span style="font-size: 1.15rem; font-weight: 900; color: #00e07e; font-family: ui-monospace, monospace;">
                            {{ $healthy_rate }}%
                        </span>
                    </div>

                    <div class="noc-progress-track">
                        <div class="noc-progress-bar" style="width: {{ $healthy_rate }}%"></div>
                    </div>

                    <div style="display: flex; justify-content: space-between; font-size: 0.6875rem; color: #9aa49f; padding-top: 0.15rem;">
                        <span>Aktif: <strong style="color: #eef1f0;">{{ $active_customers }}</strong></span>
                        <span>Isolir: <strong style="color: #f27b7b;">{{ $isolated_customers }}</strong></span>
                    </div>
                </div>
            </div>
        </div>
    </x-filament::section>
</div>
