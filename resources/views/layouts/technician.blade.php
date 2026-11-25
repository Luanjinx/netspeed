<!DOCTYPE html>
<html lang="id" class="dark scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>@yield('title', 'Portal Teknisi') - {{ $companyName ?? 'NetSpeed ISP' }}</title>

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
                <a href="{{ route('technician.dashboard') }}" class="flex items-center gap-2.5">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo" class="w-9 h-9 object-contain drop-shadow-[0_2px_8px_rgba(0,224,126,0.35)]">
                    <div>
                        <div class="flex items-center gap-1.5">
                            <span class="font-bold text-sm tracking-tight text-white">{{ $companyName ?? 'NetSpeed' }}</span>
                            <span class="text-[9px] font-extrabold uppercase tracking-widest px-1.5 py-0.5 rounded bg-accent/20 text-accent border border-accent/30">TEKNISI</span>
                        </div>
                        <p class="text-[10px] text-muted">Field Operations Portal</p>
                    </div>
                </a>
            </div>

            <!-- Desktop Nav Menu -->
            <nav class="hidden md:flex items-center gap-1 text-xs font-semibold">
                <a href="{{ route('technician.dashboard') }}" class="px-3 py-1.5 rounded-lg transition {{ request()->routeIs('technician.dashboard') ? 'bg-surface2 text-accent border border-border' : 'text-muted hover:text-white' }}">
                    Tugas Saya
                </a>
                <a href="{{ route('technician.pool-tickets') }}" class="px-3 py-1.5 rounded-lg transition flex items-center gap-1.5 {{ request()->routeIs('technician.pool-tickets') ? 'bg-surface2 text-accent border border-border' : 'text-muted hover:text-white' }}">
                    <span>Pool Tiket</span>
                </a>
                <a href="{{ route('technician.package-requests') }}" class="px-3 py-1.5 rounded-lg transition flex items-center gap-1.5 {{ request()->routeIs('technician.package-requests*') ? 'bg-surface2 text-accent border border-border' : 'text-muted hover:text-white' }}">
                    <span>Persetujuan</span>
                </a>
                <a href="{{ route('technician.create-customer') }}" class="px-3 py-1.5 rounded-lg transition flex items-center gap-1.5 {{ request()->routeIs('technician.create-customer') ? 'bg-surface2 text-accent border border-border' : 'text-muted hover:text-white' }}">
                    <span class="material-symbols-rounded text-sm text-accent">add_circle</span>
                    <span>Pasang Baru</span>
                </a>
                <a href="{{ route('technician.monitoring') }}" class="px-3 py-1.5 rounded-lg transition {{ request()->routeIs('technician.monitoring') ? 'bg-surface2 text-accent border border-border' : 'text-muted hover:text-white' }}">
                    Monitoring
                </a>
                <a href="{{ route('tech.map') }}" target="_blank" class="px-3 py-1.5 rounded-lg transition text-muted hover:text-white flex items-center gap-1">
                    <span class="material-symbols-rounded text-sm text-accent">map</span>
                    <span>Peta ODP</span>
                </a>
                <a href="{{ route('technician.history') }}" class="px-3 py-1.5 rounded-lg transition {{ request()->routeIs('technician.history') ? 'bg-surface2 text-accent border border-border' : 'text-muted hover:text-white' }}">
                    Riwayat
                </a>
            </nav>

            <!-- Header Right Menu -->
            <div class="flex items-center gap-2.5">
                <a href="{{ route('technician.create-customer') }}" class="md:hidden inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-accent text-ink font-bold text-xs">
                    <span class="material-symbols-rounded text-base">add</span>
                    <span>PSB</span>
                </a>

                <div class="flex items-center gap-2 pl-2 border-l border-border">
                    <span class="w-2 h-2 rounded-full bg-accent animate-pulse"></span>
                    <a href="{{ route('technician.profile') }}" class="text-xs font-semibold text-white hidden sm:inline hover:text-accent transition">{{ Auth::user()->name }}</a>
                    <form action="{{ route('customer.logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" title="Logout" class="p-2 rounded-lg text-muted hover:text-danger hover:bg-surface2 transition">
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

    <!-- Mobile Bottom Navigation Bar (Sticky 5 Tabs) -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 z-50 bg-surface/95 backdrop-blur-md border-t border-border px-3 py-1.5 shadow-xl shadow-black">
        <div class="flex items-center justify-around">
            <a href="{{ route('technician.dashboard') }}" class="flex flex-col items-center gap-0.5 py-1 text-xs {{ request()->routeIs('technician.dashboard*') ? 'text-accent font-bold' : 'text-muted' }}">
                <span class="material-symbols-rounded text-xl {{ request()->routeIs('technician.dashboard*') ? 'icon-fill' : '' }}">assignment</span>
                <span class="text-[10px]">Tugas</span>
            </a>

            <a href="{{ route('technician.pool-tickets') }}" class="flex flex-col items-center gap-0.5 py-1 text-xs relative {{ request()->routeIs('technician.pool-tickets*') ? 'text-accent font-bold' : 'text-muted' }}">
                <span class="material-symbols-rounded text-xl {{ request()->routeIs('technician.pool-tickets*') ? 'icon-fill' : '' }}">inbox</span>
                <span class="text-[10px]">Pool Tiket</span>
            </a>

            <a href="{{ route('technician.create-customer') }}" class="flex flex-col items-center gap-0.5 py-1 text-xs {{ request()->routeIs('technician.create-customer*') ? 'text-accent font-bold' : 'text-muted' }}">
                <span class="material-symbols-rounded text-xl {{ request()->routeIs('technician.create-customer*') ? 'icon-fill' : '' }}">person_add</span>
                <span class="text-[10px]">Pasang Baru</span>
            </a>

            <a href="{{ route('tech.map') }}" target="_blank" class="flex flex-col items-center gap-0.5 py-1 text-xs text-muted hover:text-accent">
                <span class="material-symbols-rounded text-xl">map</span>
                <span class="text-[10px]">Peta ODP</span>
            </a>

            <a href="{{ route('technician.monitoring') }}" class="flex flex-col items-center gap-0.5 py-1 text-xs {{ request()->routeIs('technician.monitoring*') ? 'text-accent font-bold' : 'text-muted' }}">
                <span class="material-symbols-rounded text-xl {{ request()->routeIs('technician.monitoring*') ? 'icon-fill' : '' }}">query_stats</span>
                <span class="text-[10px]">Monitoring</span>
            </a>
        </div>
    </nav>

    @stack('scripts')
</body>
</html>
