<!DOCTYPE html>
<html lang="id" class="dark scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>@yield('title', 'Portal Loket Agen') - {{ $companyName ?? 'NetSpeed ISP' }}</title>

    <!-- Google Fonts: Plus Jakarta Sans & Material Symbols -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    },
                    colors: {
                        bg: '#0c0e0d',
                        surface: '#131615',
                        surface2: '#1a1e1c',
                        border: '#232826',
                        text: '#eef1f0',
                        muted: '#9aa49f',
                        accent: {
                            DEFAULT: '#00e07e',
                            hover: '#4deda4',
                            glow: 'rgba(0, 224, 126, 0.25)',
                        },
                        ink: '#06261a',
                        soft: '#0f3a25',
                        danger: '#f27b7b',
                        warn: '#f2c56b',
                    }
                }
            }
        }
    </script>

    <style>
        :root {
            --bg: #0c0e0d;
            --surface: #131615;
            --surface2: #1a1e1c;
            --border: #232826;
            --text: #eef1f0;
            --muted: #9aa49f;
            --accent: #00e07e;
            --accent2: #4deda4;
            --ink: #06261a;
            --soft: #0f3a25;
            --danger: #f27b7b;
            --warn: #f2c56b;
        }

        body {
            background-color: var(--bg);
            color: var(--text);
            font-family: 'Plus Jakarta Sans', sans-serif;
            -webkit-tap-highlight-color: transparent;
        }

        .material-symbols-rounded {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            vertical-align: middle;
            line-height: 1;
        }

        .icon-fill {
            font-variation-settings: 'FILL' 1;
        }

        .ambient-glow {
            position: fixed;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100vw;
            height: 380px;
            background: radial-gradient(ellipse 65% 55% at 50% -15%, rgba(0, 224, 126, 0.12), transparent);
            pointer-events-none;
            z-index: 0;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col bg-bg text-text antialiased selection:bg-accent selection:text-ink pb-24 md:pb-12">

    <!-- Ambient Glow -->
    <div class="ambient-glow"></div>

    <!-- Top Navigation Bar -->
    <header class="sticky top-0 z-40 w-full border-b border-border bg-surface/90 backdrop-blur-md">
        <div class="max-w-6xl mx-auto px-4 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('agent.dashboard') }}" class="flex items-center gap-2.5">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo" class="w-9 h-9 object-contain drop-shadow-[0_2px_8px_rgba(0,224,126,0.35)]">
                    <div>
                        <div class="flex items-center gap-1.5">
                            <span class="font-bold text-sm tracking-tight text-white">{{ $companyName ?? 'NetSpeed' }}</span>
                            <span class="text-[9px] font-extrabold uppercase tracking-widest px-1.5 py-0.5 rounded bg-accent/20 text-accent border border-accent/30">LOKET AGEN</span>
                        </div>
                        <p class="text-[10px] text-muted">Portal Pembayaran Mitra</p>
                    </div>
                </a>
            </div>

            <!-- Desktop Nav Menu -->
            <nav class="hidden md:flex items-center gap-1 text-xs font-semibold">
                <a href="{{ route('agent.dashboard') }}" class="px-3 py-1.5 rounded-lg transition {{ request()->routeIs('agent.dashboard') ? 'bg-surface2 text-accent border border-border' : 'text-muted hover:text-white' }}">
                    Dashboard
                </a>
                <a href="{{ route('agent.payment-point') }}" class="px-3 py-1.5 rounded-lg transition flex items-center gap-1.5 {{ request()->routeIs('agent.payment-point') ? 'bg-surface2 text-accent border border-border' : 'text-muted hover:text-white' }}">
                    <span class="material-symbols-rounded text-sm text-accent">point_of_sale</span>
                    <span>Loket PPPoE</span>
                </a>
                <a href="{{ route('agent.history') }}" class="px-3 py-1.5 rounded-lg transition {{ request()->routeIs('agent.history') ? 'bg-surface2 text-accent border border-border' : 'text-muted hover:text-white' }}">
                    Riwayat Transaksi
                </a>
                <a href="{{ route('agent.profile') }}" class="px-3 py-1.5 rounded-lg transition {{ request()->routeIs('agent.profile') ? 'bg-surface2 text-accent border border-border' : 'text-muted hover:text-white' }}">
                    Profil & Sandi
                </a>
            </nav>

            <!-- Header Right Menu -->
            <div class="flex items-center gap-2.5">
                @php
                    $activeAgent = Auth::user()?->role === 'agent' ? Auth::user()?->agent : \App\Models\Agent::where('is_active', true)->first();
                @endphp

                @if($activeAgent)
                    <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-xl bg-surface2 border border-border">
                        <span class="material-symbols-rounded text-accent text-sm">account_balance_wallet</span>
                        <div class="text-[11px]">
                            <span class="text-muted block text-[9px] uppercase font-bold">Saldo Loket</span>
                            <span class="font-extrabold text-accent">{{ $activeAgent->formatted_balance }}</span>
                        </div>
                    </div>
                @endif

                <a href="{{ route('agent.payment-point') }}" class="md:hidden inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-accent text-ink font-bold text-xs">
                    <span class="material-symbols-rounded text-base">point_of_sale</span>
                    <span>Loket</span>
                </a>

                <div class="flex items-center gap-2 pl-2 border-l border-border">
                    <span class="w-2 h-2 rounded-full bg-accent animate-pulse"></span>
                    <a href="{{ route('agent.profile') }}" class="text-xs font-semibold text-white hidden sm:inline hover:text-accent transition">
                        {{ Auth::user()->name }}
                    </a>
                    <form action="{{ route('customer.logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" title="Keluar" class="p-2 rounded-lg text-muted hover:text-danger hover:bg-surface2 transition">
                            <span class="material-symbols-rounded text-lg">logout</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="flex-1 max-w-6xl w-full mx-auto px-4 py-6 relative z-10">
        @if (session('success'))
            <div class="mb-5 flex items-center gap-3 p-3.5 rounded-xl bg-accent/10 border border-accent/30 text-accent text-xs sm:text-sm font-semibold">
                <span class="material-symbols-rounded text-lg">check_circle</span>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-5 flex items-center gap-3 p-3.5 rounded-xl bg-danger/10 border border-danger/30 text-danger text-xs sm:text-sm font-semibold">
                <span class="material-symbols-rounded text-lg">error</span>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Mobile Bottom Navigation Bar (Sticky 4 Tabs) -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 z-50 bg-surface/95 backdrop-blur-md border-t border-border px-3 py-1.5 shadow-xl shadow-black">
        <div class="flex items-center justify-around">
            <a href="{{ route('agent.dashboard') }}" class="flex flex-col items-center gap-0.5 py-1 text-xs {{ request()->routeIs('agent.dashboard*') ? 'text-accent font-bold' : 'text-muted' }}">
                <span class="material-symbols-rounded text-xl {{ request()->routeIs('agent.dashboard*') ? 'icon-fill' : '' }}">dashboard</span>
                <span class="text-[10px]">Dashboard</span>
            </a>

            <a href="{{ route('agent.payment-point') }}" class="flex flex-col items-center gap-0.5 py-1 text-xs {{ request()->routeIs('agent.payment-point*') ? 'text-accent font-bold' : 'text-muted' }}">
                <span class="material-symbols-rounded text-xl {{ request()->routeIs('agent.payment-point*') ? 'icon-fill' : '' }}">point_of_sale</span>
                <span class="text-[10px]">Loket PPPoE</span>
            </a>

            <a href="{{ route('agent.history') }}" class="flex flex-col items-center gap-0.5 py-1 text-xs {{ request()->routeIs('agent.history*') ? 'text-accent font-bold' : 'text-muted' }}">
                <span class="material-symbols-rounded text-xl {{ request()->routeIs('agent.history*') ? 'icon-fill' : '' }}">history</span>
                <span class="text-[10px]">Riwayat</span>
            </a>

            <a href="{{ route('agent.profile') }}" class="flex flex-col items-center gap-0.5 py-1 text-xs {{ request()->routeIs('agent.profile*') ? 'text-accent font-bold' : 'text-muted' }}">
                <span class="material-symbols-rounded text-xl {{ request()->routeIs('agent.profile*') ? 'icon-fill' : '' }}">account_circle</span>
                <span class="text-[10px]">Profil</span>
            </a>
        </div>
    </nav>

    @stack('scripts')
</body>
</html>
