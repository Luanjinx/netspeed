<!DOCTYPE html>
<html lang="id" class="dark scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Portal Pelanggan') - {{ $companyName ?? 'NetSpeed ISP' }}</title>

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
            --chk: #191d1b;
            --shadow: 0 1px 0 #ffffff08 inset;
            --btn: #00e07e;
            --btnText: #06261a;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg);
            color: var(--text);
            min-height: 100vh;
        }

        .glass-nav {
            background: rgba(12, 14, 13, 0.92);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
        }

        .glass-card {
            background: var(--surface);
            border: 1px solid var(--border);
            box-shadow: var(--shadow), 0 10px 30px -10px rgba(0, 0, 0, 0.5);
            border-radius: 1.25rem;
        }

        .glass-card-accent {
            border-color: rgba(0, 224, 126, 0.35);
            box-shadow: var(--shadow), 0 10px 30px -10px rgba(0, 224, 126, 0.15);
        }

        /* Mascot Float Keyframes */
        @keyframes floatMascot {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-6px) rotate(1.5deg); }
        }
        .float-anim {
            animation: floatMascot 4s ease-in-out infinite;
        }

        .btn-primary {
            background-color: var(--btn);
            color: var(--btnText);
            transition: all 0.2s ease-in-out;
        }
        .btn-primary:hover {
            background-color: var(--accent2);
            box-shadow: 0 6px 18px -4px rgba(0, 224, 126, 0.4);
        }

        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: var(--bg); }
        ::-webkit-scrollbar-thumb { background: #232826; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #00e07e; }
    </style>
    @stack('styles')
</head>
<body class="antialiased flex flex-col justify-between selection:bg-[#00e07e] selection:text-[#06261a]">

    <!-- Top Navigation Bar -->
    <header class="sticky top-0 z-40 glass-nav">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between gap-4">
            <!-- Brand & Status -->
            <div class="flex items-center gap-4">
                <a href="{{ route('customer.dashboard') }}" class="flex items-center gap-3 group">
                    <img src="{{ asset('images/logo.png') }}" alt="{{ $companyName ?? 'NetSpeed' }} Logo" class="w-10 h-10 object-contain group-hover:scale-105 transition-transform duration-300 drop-shadow-[0_4px_16px_rgba(0,224,126,0.35)]">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-base sm:text-lg font-extrabold tracking-tight text-white whitespace-nowrap">
                                {{ $companyName ?? 'NetSpeed' }}
                            </span>
                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-[#00e07e]/15 text-[#00e07e] border border-[#00e07e]/30 uppercase">
                                Portal
                            </span>
                        </div>
                        <p class="text-[10px] text-[#9aa49f] font-medium">Layanan Mandiri Pelanggan</p>
                    </div>
                </a>
            </div>

            <!-- Nav Links Desktop -->
            <nav class="hidden md:flex items-center gap-3 text-sm font-semibold">
                <a href="{{ route('customer.dashboard') }}" class="flex items-center gap-2 px-3.5 py-2 rounded-xl {{ request()->routeIs('customer.dashboard') ? 'bg-[#1a1e1c] text-[#00e07e] border border-[#232826] font-bold' : 'text-[#9aa49f] hover:text-white hover:bg-[#131615]' }} transition">
                    <span class="material-symbols-rounded text-lg">dashboard</span>
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('customer.tickets') }}" class="flex items-center gap-2 px-3.5 py-2 rounded-xl {{ request()->routeIs('customer.tickets*') ? 'bg-[#1a1e1c] text-[#00e07e] border border-[#232826] font-bold' : 'text-[#9aa49f] hover:text-white hover:bg-[#131615]' }} transition">
                    <span class="material-symbols-rounded text-lg">confirmation_number</span>
                    <span>Tiket Gangguan</span>
                </a>
                <a href="{{ route('customer.profile') }}" class="flex items-center gap-2 px-3.5 py-2 rounded-xl {{ request()->routeIs('customer.profile') ? 'bg-[#1a1e1c] text-[#00e07e] border border-[#232826] font-bold' : 'text-[#9aa49f] hover:text-white hover:bg-[#131615]' }} transition">
                    <span class="material-symbols-rounded text-lg">settings</span>
                    <span>Pengaturan</span>
                </a>
            </nav>

            <!-- User Menu & Logout -->
            <div class="flex items-center gap-3">
                @if(isset($companyPhone) && $companyPhone)
                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $companyPhone) }}" target="_blank" class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-[#00e07e]/10 text-[#00e07e] border border-[#00e07e]/20 hover:bg-[#00e07e]/20 transition">
                        <span class="material-symbols-rounded text-base">chat</span>
                        <span>CS WA</span>
                    </a>
                @endif

                <form method="POST" action="{{ route('customer.logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold rounded-lg bg-[#1a1e1c] hover:bg-[#f27b7b]/15 hover:text-[#f27b7b] text-[#9aa49f] border border-[#232826] hover:border-[#f27b7b]/30 transition">
                        <span class="material-symbols-rounded text-base">logout</span>
                        <span class="hidden sm:inline">Keluar</span>
                    </button>
                </form>

                <!-- Mobile Hamburger Button -->
                <button id="mobileMenuBtn" type="button" class="md:hidden p-2 rounded-lg bg-[#1a1e1c] text-[#9aa49f] hover:text-white border border-[#232826]">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile Menu Dropdown -->
        <div id="mobileMenu" class="hidden md:hidden bg-[#131615] border-b border-[#232826] px-4 py-3 space-y-2">
            <a href="{{ route('customer.dashboard') }}" class="flex items-center gap-2 py-2 text-sm {{ request()->routeIs('customer.dashboard') ? 'text-[#00e07e] font-bold' : 'text-[#9aa49f]' }}">
                <span class="material-symbols-rounded text-base">dashboard</span>
                <span>Dashboard Utama</span>
            </a>
            <a href="{{ route('customer.tickets') }}" class="flex items-center gap-2 py-2 text-sm {{ request()->routeIs('customer.tickets*') ? 'text-[#00e07e] font-bold' : 'text-[#9aa49f]' }}">
                <span class="material-symbols-rounded text-base">confirmation_number</span>
                <span>Tiket Kendala Jaringan</span>
            </a>
            <a href="{{ route('customer.profile') }}" class="flex items-center gap-2 py-2 text-sm {{ request()->routeIs('customer.profile') ? 'text-[#00e07e] font-bold' : 'text-[#9aa49f]' }}">
                <span class="material-symbols-rounded text-base">settings</span>
                <span>Profil & Password</span>
            </a>
        </div>
    </header>

    <!-- Flash Alerts -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full flex-grow">
        @if (session('success'))
            <div class="mb-6 p-4 rounded-xl bg-[#00e07e]/10 border border-[#00e07e]/30 text-[#00e07e] flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="text-xl">✅</span>
                    <span class="text-sm font-semibold">{{ session('success') }}</span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-[#9aa49f] hover:text-white">&times;</button>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 p-4 rounded-xl bg-[#f27b7b]/10 border border-[#f27b7b]/30 text-[#f27b7b] flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="text-xl">⚠️</span>
                    <span class="text-sm font-semibold">{{ session('error') }}</span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-[#9aa49f] hover:text-white">&times;</button>
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 p-4 rounded-xl bg-[#f27b7b]/10 border border-[#f27b7b]/30 text-[#f27b7b]">
                <div class="font-bold text-sm mb-1">Terjadi kesalahan:</div>
                <ul class="list-disc list-inside text-xs space-y-1">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="border-t border-[#232826] bg-[#0c0e0d] py-6 text-center text-xs text-[#9aa49f]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-3">
            <p>&copy; {{ date('Y') }} {{ $companyName ?? 'NetSpeed' }}. Seluruh hak cipta dilindungi.</p>
            <div class="flex items-center gap-4 text-[#9aa49f]">
                <a href="{{ url('/') }}" class="hover:text-white transition">Beranda Utama</a>
                <span>•</span>
                <a href="{{ route('customer.tickets') }}" class="hover:text-white transition">Pusat Bantuan</a>
            </div>
        </div>
    </footer>

    <script>
        const mobileBtn = document.getElementById('mobileMenuBtn');
        const mobileNav = document.getElementById('mobileMenu');
        if (mobileBtn && mobileNav) {
            mobileBtn.addEventListener('click', () => {
                mobileNav.classList.toggle('hidden');
            });
        }
    </script>
    @stack('scripts')
</body>
</html>
