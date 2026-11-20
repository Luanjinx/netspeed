<!DOCTYPE html>
<html lang="id" class="dark scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $companyName }} - High-Speed Broadband & Wi-Fi Hotspot</title>
    <meta name="description" content="Layanan internet broadband ultra cepat, stabil, dan terjangkau untuk rumah, warkop, dan bisnis dengan jaminan uptime 99.9% dan latensi rendah.">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">

    <!-- Google Fonts: Plus Jakarta Sans & Material Symbols -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
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
            overflow-x: hidden;
            min-height: 100vh;
        }

        .material-symbols-rounded {
            font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            vertical-align: middle;
            line-height: 1;
            display: inline-block;
        }

        /* Glassmorphism & Surface Cards */
        .glass-nav {
            background: rgba(12, 14, 13, 0.9);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            border-bottom: 1px solid var(--border);
        }

        .glass-card {
            background: var(--surface);
            border: 1px solid var(--border);
            box-shadow: var(--shadow), 0 10px 30px -10px rgba(0, 0, 0, 0.5);
            border-radius: 1.25rem;
        }

        .glass-card:hover {
            border-color: rgba(0, 224, 126, 0.4);
        }

        .glass-card-accent {
            border-color: rgba(0, 224, 126, 0.35);
            box-shadow: var(--shadow), 0 12px 35px -10px rgba(0, 224, 126, 0.18);
        }

        /* Ambient Glow Effect */
        .ambient-glow {
            position: absolute;
            width: 550px;
            height: 550px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(0, 224, 126, 0.08) 0%, rgba(15, 58, 37, 0.04) 50%, transparent 70%);
            filter: blur(75px);
            pointer-events: none;
            z-index: 0;
        }

        /* Mascot Floating Animation */
        @keyframes floatMascot {
            0%, 100% {
                transform: translateY(0px) rotate(0deg);
            }
            50% {
                transform: translateY(-8px) rotate(2deg);
            }
        }
        .float-anim {
            animation: floatMascot 4s ease-in-out infinite;
        }

        /* Speedometer Pulse Animation */
        @keyframes pulseGlow {
            0%, 100% { opacity: 0.7; transform: scale(1); }
            50% { opacity: 1; transform: scale(1.02); }
        }
        .pulse-anim {
            animation: pulseGlow 3s infinite ease-in-out;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 7px;
        }
        ::-webkit-scrollbar-track {
            background: var(--bg);
        }
        ::-webkit-scrollbar-thumb {
            background: var(--border);
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: var(--accent);
        }
    </style>
</head>
<body class="antialiased selection:bg-[#00e07e] selection:text-[#06261a]">

    <!-- Top Announcement Bar (Clean & Compact) -->
    <div class="bg-[#080a09] border-b border-[#232826] text-xs text-[#9aa49f] py-2.5 px-4 sm:px-6 relative z-50">
        <div class="max-w-7xl mx-auto flex items-center justify-between gap-4">
            <div class="flex items-center gap-2.5 truncate">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-[#0f3a25] border border-[#00e07e]/30 text-[#00e07e] font-bold text-[11px] shrink-0">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#00e07e] animate-pulse"></span>
                    Core Network 100% Operational
                </span>
                <span class="hidden md:inline text-[#232826]">|</span>
                <span class="hidden md:inline text-[#9aa49f] truncate">Direct Peering: IIX, OpenIXP, Google, Cloudflare & Valve Server</span>
            </div>
            <div class="flex items-center gap-3 sm:gap-4 shrink-0 text-xs">
                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $companyPhone) }}" target="_blank" class="hidden sm:flex items-center gap-1.5 text-[#eef1f0] hover:text-[#00e07e] transition">
                    <span class="material-symbols-rounded text-sm text-[#00e07e]">chat</span>
                    <span>CS:</span>
                    <strong class="text-white">{{ $companyPhone }}</strong>
                </a>
                <span class="hidden sm:inline text-[#232826]">|</span>
                <a href="{{ route('customer.dashboard') }}" class="hover:text-[#00e07e] text-[#eef1f0] font-semibold transition flex items-center gap-1.5">
                    <span class="material-symbols-rounded text-sm text-[#00e07e]">account_circle</span>
                    <span>Portal Pelanggan</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Main Navigation Bar -->
    <header class="sticky top-0 z-40 glass-nav transition-all">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between gap-4">
            
            <!-- Brand Logo -->
            <a href="{{ route('landing') }}" class="flex items-center gap-3 group shrink-0">
                <img src="{{ asset('images/logo.png') }}" alt="{{ $companyName }} Logo" class="w-10 h-10 object-contain group-hover:scale-105 transition-transform duration-300 drop-shadow-[0_4px_16px_rgba(0,224,126,0.35)]">
                <div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-lg sm:text-xl font-extrabold tracking-tight text-[#eef1f0] whitespace-nowrap">
                            {{ $companyName }}
                        </span>
                        <span class="text-[10px] font-extrabold px-1.5 py-0.5 rounded bg-[#0f3a25] text-[#00e07e] border border-[#00e07e]/30 uppercase">
                            BROADBAND
                        </span>
                    </div>
                    <p class="text-[10px] font-semibold tracking-wider uppercase text-[#9aa49f] whitespace-nowrap">High-Speed Broadband</p>
                </div>
            </a>

            <!-- Desktop Menu (Hidden on Mobile) -->
            <nav class="hidden lg:flex items-center gap-7 text-sm font-semibold text-[#9aa49f] whitespace-nowrap">
                <a href="#fitur" class="hover:text-[#eef1f0] transition">Keunggulan</a>
                <a href="#paket" class="hover:text-[#eef1f0] transition">Paket Internet</a>
                <a href="#hotspot" class="hover:text-[#eef1f0] transition">Voucher Hotspot</a>
                <a href="#kontak" class="hover:text-[#eef1f0] transition">Kontak</a>
            </nav>

            <!-- Desktop Action Buttons (Hidden on Mobile) -->
            <div class="hidden lg:flex items-center gap-3 shrink-0">
                <a href="{{ route('customer.dashboard') }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-bold rounded-xl bg-[#1a1e1c] hover:bg-[#232826] text-[#eef1f0] border border-[#232826] transition">
                    <span class="w-2 h-2 rounded-full bg-[#00e07e]"></span>
                    <span>Portal Pelanggan</span>
                </a>
                <a href="#cek-tagihan" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold rounded-xl bg-[#1a1e1c] hover:bg-[#232826] text-[#eef1f0] border border-[#232826] transition">
                    <span class="material-symbols-rounded text-base text-[#00e07e]">receipt_long</span>
                    <span>Cek Tagihan</span>
                </a>
                <a href="#daftar" class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-black rounded-xl bg-[#00e07e] hover:bg-[#4deda4] text-[#06261a] shadow-lg shadow-[#00e07e]/20 transition transform hover:-translate-y-0.5">
                    <span class="material-symbols-rounded text-base">rocket_launch</span>
                    <span>Pasang Sekarang</span>
                </a>
            </div>

            <!-- Mobile Hamburger Button (Visible on Mobile/Tablet < lg) -->
            <div class="flex items-center gap-2 lg:hidden">
                <a href="#cek-tagihan" class="sm:inline-flex hidden items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-lg bg-[#1a1e1c] text-[#eef1f0] border border-[#232826]">
                    <span class="material-symbols-rounded text-sm text-[#00e07e]">receipt_long</span>
                    <span>Cek Tagihan</span>
                </a>
                <button id="mobileMenuBtn" type="button" class="p-2.5 rounded-xl bg-[#1a1e1c] text-[#eef1f0] hover:text-white border border-[#232826] focus:outline-none transition" aria-label="Buka Menu Navigasi">
                    <!-- Hamburger icon (3 bars) -->
                    <svg id="iconHamburger" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <!-- Close X icon -->
                    <svg id="iconClose" class="w-6 h-6 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

        </div>

        <!-- Mobile Drawer Navigation (Slide-Down with Glassmorphism) -->
        <div id="mobileMenu" class="hidden lg:hidden bg-[#0c0e0d]/98 backdrop-blur-2xl border-b border-[#232826] shadow-2xl px-5 pt-4 pb-6 space-y-3 transition-all duration-300">
            <!-- Portal Pelanggan Highlight Card -->
            <a href="{{ route('customer.dashboard') }}" class="mobile-nav-link flex items-center justify-between p-3.5 rounded-xl bg-gradient-to-r from-[#131615] to-[#0f3a25]/60 border border-[#00e07e]/30 group">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-[#0f3a25] text-[#00e07e] flex items-center justify-center">
                        <span class="material-symbols-rounded text-xl">account_circle</span>
                    </div>
                    <div>
                        <div class="text-sm font-extrabold text-[#eef1f0] group-hover:text-[#00e07e] transition">Portal Mandiri Pelanggan</div>
                        <div class="text-[11px] text-[#9aa49f]">Masuk & kelola akun, Wi-Fi, dan tagihan</div>
                    </div>
                </div>
                <span class="text-[#00e07e] font-bold text-sm">&rarr;</span>
            </a>

            <!-- Nav Links List -->
            <div class="space-y-1 pt-1 font-semibold text-sm text-[#eef1f0]">
                <a href="#fitur" class="mobile-nav-link flex items-center gap-3 py-2.5 px-3 rounded-lg hover:text-white hover:bg-[#1a1e1c] transition">
                    <span class="material-symbols-rounded text-lg text-[#00e07e]">verified</span>
                    <span>Keunggulan Layanan</span>
                </a>
                <a href="#paket" class="mobile-nav-link flex items-center gap-3 py-2.5 px-3 rounded-lg hover:text-white hover:bg-[#1a1e1c] transition">
                    <span class="material-symbols-rounded text-lg text-[#00e07e]">bolt</span>
                    <span>Paket Internet Rumahan</span>
                </a>
                <a href="#hotspot" class="mobile-nav-link flex items-center gap-3 py-2.5 px-3 rounded-lg hover:text-white hover:bg-[#1a1e1c] transition">
                    <span class="material-symbols-rounded text-lg text-[#00e07e]">wifi</span>
                    <span>Voucher Hotspot Wi-Fi</span>
                </a>
                <a href="#cek-tagihan" class="mobile-nav-link flex items-center gap-3 py-2.5 px-3 rounded-lg hover:text-white hover:bg-[#1a1e1c] transition">
                    <span class="material-symbols-rounded text-lg text-[#00e07e]">receipt_long</span>
                    <span>Cek Tagihan & QRIS</span>
                </a>
                <a href="#daftar" class="mobile-nav-link flex items-center gap-3 py-2.5 px-3 rounded-lg hover:text-white hover:bg-[#1a1e1c] transition">
                    <span class="material-symbols-rounded text-lg text-[#00e07e]">rocket_launch</span>
                    <span>Formulir Pasang Baru</span>
                </a>
                <a href="#kontak" class="mobile-nav-link flex items-center gap-3 py-2.5 px-3 rounded-lg hover:text-white hover:bg-[#1a1e1c] transition">
                    <span class="material-symbols-rounded text-lg text-[#00e07e]">call</span>
                    <span>Kontak & Lokasi Kantor</span>
                </a>
            </div>

            <!-- Mobile Drawer Action Buttons -->
            <div class="pt-3 border-t border-[#232826] space-y-2">
                <a href="#daftar" class="mobile-nav-link w-full text-center py-3 rounded-xl text-xs font-black bg-[#00e07e] text-[#06261a] shadow-lg shadow-[#00e07e]/20 flex items-center justify-center gap-1.5">
                    <span class="material-symbols-rounded text-base">rocket_launch</span>
                    <span>Pasang Baru Sekarang</span>
                </a>
                <a href="#cek-tagihan" class="mobile-nav-link w-full text-center py-2.5 rounded-xl text-xs font-bold bg-[#1a1e1c] text-[#eef1f0] border border-[#232826] flex items-center justify-center gap-1.5">
                    <span class="material-symbols-rounded text-sm text-[#00e07e]">receipt_long</span>
                    <span>Cek Tagihan Cepat</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="relative pt-14 pb-20 lg:pt-24 lg:pb-32 overflow-hidden">
        <!-- Ambient Subtle Spotlight -->
        <div class="ambient-glow top-0 -left-20"></div>
        <div class="ambient-glow top-1/2 -right-20"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-8 items-center">
                
                <!-- Left Hero Copy -->
                <div class="lg:col-span-7 text-center lg:text-left space-y-6">
                    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-[#131615] border border-[#232826] text-xs font-semibold text-[#9aa49f] shadow-sm">
                        <span class="w-2 h-2 rounded-full bg-[#00e07e] animate-ping"></span>
                        Koneksi High-Speed Broadband 100% Unlimited
                    </div>

                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight leading-[1.15] text-[#eef1f0]">
                        Internet Super Cepat, <br class="hidden sm:inline">
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-white via-[#eef1f0] to-[#00e07e]">Stabil & Tanpa Batas</span>
                    </h1>

                    <p class="text-base sm:text-lg text-[#9aa49f] max-w-2xl mx-auto lg:mx-0 leading-relaxed font-normal">
                        Nikmati streaming film 4K tanpa buffering, gaming online dengan ping terendah, dan produktivitas bisnis tanpa henti didukung oleh infrastruktur jaringan broadband berkecepatan tinggi.
                    </p>

                    <!-- CTA Buttons -->
                    <div class="flex flex-wrap items-center justify-center lg:justify-start gap-4 pt-2">
                        <a href="#paket" class="px-7 py-3.5 rounded-xl font-black text-sm bg-[#00e07e] hover:bg-[#4deda4] text-[#06261a] shadow-xl shadow-[#00e07e]/20 transition transform hover:-translate-y-0.5 flex items-center gap-2">
                            <span>Pilih Paket Internet</span>
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </a>
                        <a href="#cek-tagihan" class="px-6 py-3.5 rounded-xl font-bold text-sm bg-[#1a1e1c] hover:bg-[#232826] text-[#eef1f0] border border-[#232826] transition flex items-center gap-2">
                            <span class="material-symbols-rounded text-lg text-[#00e07e]">receipt_long</span>
                            <span>Cek Tagihan & QRIS</span>
                        </a>
                    </div>

                    <!-- Highlight Badges Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-6 border-t border-[#232826]">
                        <div class="text-left">
                            <div class="text-2xl font-black text-[#eef1f0]">100%</div>
                            <div class="text-xs text-[#9aa49f] font-medium mt-0.5">Broadband Unlimited</div>
                        </div>
                        <div class="text-left">
                            <div class="text-2xl font-black text-[#00e07e]">99.9%</div>
                            <div class="text-xs text-[#9aa49f] font-medium mt-0.5">SLA Uptime Jaringan</div>
                        </div>
                        <div class="text-left">
                            <div class="text-2xl font-black text-[#00e07e]">&lt; 5ms</div>
                            <div class="text-xs text-[#9aa49f] font-medium mt-0.5">Low Latency Gaming</div>
                        </div>
                        <div class="text-left">
                            <div class="text-2xl font-black text-[#eef1f0]">24/7</div>
                            <div class="text-xs text-[#9aa49f] font-medium mt-0.5">Tim Respon Cepat</div>
                        </div>
                    </div>
                </div>

                <!-- Right Hero Visual / Live Speedmeter Simulator with Dual Mascot -->
                <div class="lg:col-span-5 relative flex justify-center pt-8 sm:pt-4">
                    <!-- Ambient Glow behind card -->
                    <div class="absolute -inset-4 bg-gradient-to-r from-[#00e07e]/15 to-[#4deda4]/10 rounded-3xl blur-2xl pointer-events-none"></div>

                    <!-- Outer Mascot 1: Mascot Speed flying & zooming at top right -->
                    <div class="absolute -top-12 -right-4 sm:-right-8 z-30 w-28 sm:w-32 filter drop-shadow-[0_12px_28px_rgba(0,224,126,0.45)] float-anim select-none pointer-events-none">
                        <img src="{{ asset('images/mascot-speed.webp') }}" alt="{{ $companyName }} Mascot Speed" class="w-full h-auto object-contain">
                    </div>

                    <!-- Outer Mascot 2: Mascot Gaming cheering at bottom left -->
                    <div class="absolute -bottom-10 -left-6 sm:-left-8 z-30 w-24 sm:w-28 filter drop-shadow-[0_10px_24px_rgba(0,224,126,0.4)] float-anim select-none pointer-events-none" style="animation-delay: -2s;">
                        <img src="{{ asset('images/mascot-gaming.webp') }}" alt="{{ $companyName }} Mascot Gaming" class="w-full h-auto object-contain">
                    </div>

                    <div class="w-full max-w-md glass-card glass-card-accent p-6 sm:p-7 relative overflow-hidden transition-all duration-300 border-[#00e07e]/40 shadow-2xl shadow-black/80">
                        <!-- Top header of card -->
                        <div class="flex items-center justify-between pb-3.5 border-b border-[#232826] relative z-10">
                            <div class="flex items-center gap-2.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-[#00e07e] shadow-[0_0_12px_#00e07e] animate-pulse"></span>
                                <span class="text-xs font-black tracking-wider uppercase text-white">{{ $companyName }} Core Gateway</span>
                            </div>
                            <span class="text-[10px] font-mono font-bold text-[#00e07e] bg-[#0f3a25] px-2.5 py-0.5 rounded-full border border-[#00e07e]/30 flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#00e07e]"></span>
                                RouterOS v7
                            </span>
                        </div>

                        <!-- Speedometer Section (Redesigned Cyber Tachometer Dial) -->
                        <div class="my-5 text-center relative z-10">
                            <div class="relative w-52 h-52 sm:w-56 sm:h-56 mx-auto flex items-center justify-center">
                                <!-- Ambient Radial Glow behind Circular Ring -->
                                <div class="absolute inset-0 rounded-full blur-2xl pointer-events-none" style="background: radial-gradient(circle, rgba(0, 224, 126, 0.25) 0%, transparent 70%);"></div>

                                <!-- Tachometer Gauge SVG (240 deg arc) -->
                                <svg class="w-full h-full transform -rotate-90 relative z-10" viewBox="0 0 180 180">
                                    <defs>
                                        <linearGradient id="speedDialGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                            <stop offset="0%" stop-color="#00e07e" />
                                            <stop offset="60%" stop-color="#4deda4" />
                                            <stop offset="100%" stop-color="#00f5ff" />
                                        </linearGradient>
                                        <filter id="neonGlowDial" x="-20%" y="-20%" width="140%" height="140%">
                                            <feGaussianBlur stdDeviation="3" result="blur" />
                                            <feComposite in="SourceGraphic" in2="blur" operator="over" />
                                        </filter>
                                    </defs>

                                    <!-- Background Track Arc (radius 70, circumference = 439.8, 75% track = ~330) -->
                                    <circle cx="90" cy="90" r="70"
                                            stroke="#1a1e1c" stroke-width="11" stroke-linecap="round" fill="none"
                                            stroke-dasharray="330 110" stroke-dashoffset="-55" />

                                    <!-- Decorative Neon Graduated Ticks -->
                                    <circle cx="90" cy="90" r="57"
                                            stroke="rgba(0, 224, 126, 0.2)" stroke-width="2.5" stroke-dasharray="2 7" fill="none" />

                                    <!-- Active Animated Speed Value Arc -->
                                    <circle id="speedDialArc" cx="90" cy="90" r="70"
                                            stroke="url(#speedDialGrad)" stroke-width="11" stroke-linecap="round" fill="none"
                                            filter="url(#neonGlowDial)"
                                            stroke-dasharray="330 110" stroke-dashoffset="60"
                                            style="transition: stroke-dashoffset 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);" />
                                </svg>

                                <!-- Center Digital Readout Display -->
                                <div class="absolute inset-0 flex flex-col items-center justify-center z-20 select-none">
                                    <span class="text-[10px] font-extrabold uppercase tracking-widest text-[#9aa49f]">Bandwidth Aktif</span>
                                    <div class="flex items-baseline justify-center gap-1 my-0.5">
                                        <span id="speedValueMain" class="text-5xl font-black text-white tracking-tight font-mono drop-shadow-[0_0_20px_rgba(0,224,126,0.65)]">100</span>
                                    </div>
                                    <span class="text-xs font-black text-[#00e07e] uppercase tracking-widest">Mbps</span>
                                    <div class="mt-2 flex items-center gap-1.5 px-3 py-0.5 rounded-full bg-[#0f3a25] border border-[#00e07e]/35 shadow-sm shadow-[#00e07e]/15">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#00e07e] animate-ping"></span>
                                        <span id="speedModeLabel" class="text-[10px] font-extrabold text-[#00e07e] tracking-wider uppercase">Simetris 1:1</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Dual Live Telemetry Counters: Download & Upload -->
                            <div class="grid grid-cols-2 gap-3 mt-5">
                                <div class="bg-[#1a1e1c] border border-[#232826] hover:border-[#00e07e]/40 rounded-xl p-3 flex items-center justify-between transition-colors">
                                    <div class="text-left">
                                        <div class="flex items-center gap-1 text-[11px] font-semibold text-[#9aa49f]">
                                            <span class="material-symbols-rounded text-sm text-[#00e07e]">arrow_downward</span>
                                            <span>Download</span>
                                        </div>
                                        <div class="text-base font-black text-white font-mono mt-0.5">
                                            <span id="speedDownloadVal">102.4</span> <span class="text-[10px] text-[#00e07e] font-sans">Mbps</span>
                                        </div>
                                    </div>
                                    <!-- Mini visualizer waveform -->
                                    <div class="flex items-end gap-1 h-6">
                                        <span class="w-1 bg-[#00e07e] rounded-full animate-pulse h-3"></span>
                                        <span class="w-1 bg-[#00e07e] rounded-full animate-pulse h-6" style="animation-delay: 150ms;"></span>
                                        <span class="w-1 bg-[#00e07e] rounded-full animate-pulse h-4" style="animation-delay: 300ms;"></span>
                                        <span class="w-1 bg-[#00e07e] rounded-full animate-pulse h-5" style="animation-delay: 450ms;"></span>
                                    </div>
                                </div>

                                <div class="bg-[#1a1e1c] border border-[#232826] hover:border-[#00e07e]/40 rounded-xl p-3 flex items-center justify-between transition-colors">
                                    <div class="text-left">
                                        <div class="flex items-center gap-1 text-[11px] font-semibold text-[#9aa49f]">
                                            <span class="material-symbols-rounded text-sm text-[#4deda4]">arrow_upward</span>
                                            <span>Upload</span>
                                        </div>
                                        <div class="text-base font-black text-white font-mono mt-0.5">
                                            <span id="speedUploadVal">98.8</span> <span class="text-[10px] text-[#4deda4] font-sans">Mbps</span>
                                        </div>
                                    </div>
                                    <!-- Mini visualizer waveform -->
                                    <div class="flex items-end gap-1 h-6">
                                        <span class="w-1 bg-[#4deda4] rounded-full animate-pulse h-5" style="animation-delay: 200ms;"></span>
                                        <span class="w-1 bg-[#4deda4] rounded-full animate-pulse h-6" style="animation-delay: 350ms;"></span>
                                        <span class="w-1 bg-[#4deda4] rounded-full animate-pulse h-3" style="animation-delay: 100ms;"></span>
                                        <span class="w-1 bg-[#4deda4] rounded-full animate-pulse h-4" style="animation-delay: 500ms;"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Interactive Speedtest Trigger Button -->
                            <div class="mt-3.5">
                                <button type="button" id="btnRunSpeedTest" onclick="runInteractiveSpeedtest()" class="w-full py-2.5 px-4 rounded-xl bg-gradient-to-r from-[#0f3a25] to-[#1a1e1c] hover:from-[#155435] hover:to-[#222a26] border border-[#00e07e]/40 text-[#00e07e] hover:text-white text-xs font-bold transition flex items-center justify-center gap-2 group shadow-md shadow-[#00e07e]/10">
                                    <span id="speedtestIcon" class="material-symbols-rounded text-base text-[#00e07e] group-hover:rotate-180 transition-transform duration-500">speed</span>
                                    <span id="speedtestBtnText">Uji Ulang Kecepatan (Speedtest)</span>
                                </button>
                            </div>
                        </div>

                        <!-- Mini stats grid: Ping, Jitter, Loss -->
                        <div class="grid grid-cols-3 gap-2 bg-[#1a1e1c] rounded-xl p-3 border border-[#232826] text-center relative z-10">
                            <div>
                                <span class="text-[10px] uppercase font-bold text-[#9aa49f] block">Ping</span>
                                <span id="speedPingVal" class="text-sm font-extrabold text-[#00e07e] font-mono">2 ms</span>
                            </div>
                            <div>
                                <span class="text-[10px] uppercase font-bold text-[#9aa49f] block">Jitter</span>
                                <span id="speedJitterVal" class="text-sm font-extrabold text-[#eef1f0] font-mono">0.6 ms</span>
                            </div>
                            <div>
                                <span class="text-[10px] uppercase font-bold text-[#9aa49f] block">Loss</span>
                                <span id="speedLossVal" class="text-sm font-extrabold text-white font-mono">0.0%</span>
                            </div>
                        </div>

                        <!-- Bottom gateway telemetry status -->
                        <div class="mt-4 pt-3 border-t border-[#232826] flex items-center justify-between text-xs text-[#9aa49f] relative z-10">
                            <span class="flex items-center gap-1.5 truncate">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#00e07e]"></span>
                                <span>Gateway: <strong class="text-white font-semibold">Tersambung (Koneksi Prima)</strong></span>
                            </span>
                            <span class="text-[11px] font-semibold text-[#00e07e] shrink-0">100% Optimal</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- Live Bill Checker Widget (Interactive) -->
    <section id="cek-tagihan" class="scroll-mt-20 py-16 bg-[#0a0c0b] border-y border-[#232826] relative">
        <div class="max-w-4xl mx-auto px-4 sm:px-6">
            <div class="text-center mb-8">
                <span class="text-xs font-extrabold text-[#00e07e] uppercase tracking-wider px-3 py-1 rounded-full bg-[#0f3a25] border border-[#00e07e]/30">
                    Self-Service Pelanggan
                </span>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-[#eef1f0] mt-2">
                    Cek Tagihan & Pembayaran Instan (QRIS)
                </h2>
                <p class="text-sm text-[#9aa49f] mt-1">
                    Cukup masukkan Email atau Nomor WhatsApp Anda untuk melihat rincian tagihan dan scan QRIS.
                </p>
            </div>

            <!-- Search Form Card -->
            <div class="glass-card p-6 sm:p-8">
                <form id="billCheckForm" class="space-y-4">
                    @csrf
                    <div class="flex flex-col sm:flex-row gap-3">
                        <div class="relative flex-1">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#9aa49f]">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input type="text"
                                   id="billSearchInput"
                                   name="search"
                                   placeholder="Contoh: 08123456789 atau email@gmail.com"
                                   required
                                   class="w-full pl-11 pr-4 py-3.5 rounded-xl bg-[#1a1e1c] border border-[#232826] text-[#eef1f0] placeholder-[#9aa49f] focus:outline-none focus:border-[#00e07e] focus:ring-1 focus:ring-[#00e07e] text-sm font-medium transition">
                        </div>
                        <button type="submit"
                                id="btnCheckBill"
                                class="px-7 py-3.5 rounded-xl font-black text-sm bg-[#00e07e] hover:bg-[#4deda4] text-[#06261a] shadow-lg shadow-[#00e07e]/20 transition shrink-0 flex items-center justify-center gap-2">
                            <span class="material-symbols-rounded text-base">search</span>
                            <span>Cek Tagihan</span>
                        </button>
                    </div>
                </form>

                <!-- Feedback Message -->
                <div id="billAlert" class="hidden mt-4 p-4 rounded-xl text-sm"></div>

                <!-- Result Card (Populated by JS) -->
                <div id="billResultCard" class="hidden mt-6 pt-6 border-t border-[#232826] space-y-6">
                    <div class="flex flex-wrap items-center justify-between gap-4 bg-[#1a1e1c] p-4 rounded-xl border border-[#232826]">
                        <div>
                            <span class="text-xs text-[#9aa49f] block uppercase font-bold tracking-wider">Pelanggan</span>
                            <h3 id="resCustName" class="text-lg font-extrabold text-[#eef1f0]"></h3>
                            <div class="flex items-center gap-2 mt-1 text-xs text-[#9aa49f]">
                                <span>ID: <strong id="resCustId" class="text-[#eef1f0]"></strong></span>
                                <span>•</span>
                                <span>Paket: <strong id="resCustPkg" class="text-[#00e07e]"></strong></span>
                            </div>
                        </div>
                        <div id="resBadgeStatus"></div>
                    </div>

                    <!-- Bill Details Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Invoice Breakdown -->
                        <div class="bg-[#1a1e1c]/60 p-5 rounded-xl border border-[#232826] space-y-3">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-[#9aa49f]">Rincian Tagihan</h4>
                            <div class="flex justify-between text-sm py-1 border-b border-[#232826]">
                                <span class="text-[#9aa49f]">Nomor Invoice</span>
                                <span id="resInvNumber" class="font-mono font-bold text-[#eef1f0]"></span>
                            </div>
                            <div class="flex justify-between text-sm py-1 border-b border-[#232826]">
                                <span class="text-[#9aa49f]">Periode</span>
                                <span id="resInvPeriod" class="font-bold text-[#eef1f0]"></span>
                            </div>
                            <div class="flex justify-between text-sm py-1 border-b border-[#232826]">
                                <span class="text-[#9aa49f]">Jatuh Tempo</span>
                                <span id="resInvDue" class="font-bold text-[#f27b7b]"></span>
                            </div>
                            <div class="flex justify-between text-base pt-2">
                                <span class="font-bold text-white">Total Tagihan</span>
                                <span id="resInvAmount" class="text-xl font-extrabold text-[#00e07e]"></span>
                            </div>
                        </div>

                        <!-- Dynamic QRIS / Status Container -->
                        <div class="bg-[#1a1e1c]/60 p-5 rounded-xl border border-[#232826] text-center flex flex-col items-center justify-center">
                            <!-- QRIS Unpaid Container -->
                            <div id="qrisContainer" class="w-full flex flex-col items-center">
                                <div class="text-xs font-bold uppercase tracking-wider text-[#9aa49f] mb-2">Scan QRIS untuk Bayar</div>
                                <div id="qrisImgBox" class="p-2 bg-white rounded-xl shadow-lg shadow-black/50">
                                    <!-- QR Image injected here -->
                                </div>
                                <p class="text-[11px] text-[#9aa49f] mt-2 max-w-xs">
                                    Mendukung GoPay, OVO, Dana, ShopeePay, BCA, Mandiri, BRImo, LinkAja, dan seluruh aplikasi m-Banking.
                                </p>
                            </div>

                            <!-- Paid / Normal Status Container -->
                            <div id="paidContainer" class="hidden py-4 text-center">
                                <span class="w-12 h-12 rounded-full bg-[#0f3a25] text-[#00e07e] flex items-center justify-center mx-auto mb-2 border border-[#00e07e]/30">
                                    <span class="material-symbols-rounded text-2xl text-[#00e07e]">check_circle</span>
                                </span>
                                <h5 class="text-base font-bold text-[#00e07e]">Tidak Ada Tagihan Tertunggak</h5>
                                <p class="text-xs text-[#9aa49f] mt-1 max-w-xs">Terima kasih atas pembayaran Anda. Layanan internet Anda aktif dan dapat digunakan dengan normal.</p>
                            </div>

                            <!-- Isolated Status Warning Container -->
                            <div id="isolatedContainer" class="hidden py-4 text-center">
                                <span class="w-12 h-12 rounded-full bg-rose-500/15 text-[#f27b7b] flex items-center justify-center mx-auto mb-2 border border-rose-500/30">
                                    <span class="material-symbols-rounded text-2xl text-[#f27b7b]">gpp_maybe</span>
                                </span>
                                <h5 class="text-base font-bold text-[#f27b7b]">Layanan Saat Ini Terisolir</h5>
                                <p class="text-xs text-[#9aa49f] mt-1 max-w-xs">Layanan internet Anda dinonaktifkan sementara. Silakan hubungi tim Customer Service untuk konfirmasi tagihan berjalan atau aktivasi ulang.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Action Link to WhatsApp -->
                    <div class="text-center pt-2">
                        <a id="resWaConfirmBtn" href="#" target="_blank" class="inline-flex items-center gap-2 text-xs font-bold text-[#00e07e] hover:underline transition">
                            <span class="material-symbols-rounded text-sm">chat</span>
                            <span id="resWaConfirmText">Konfirmasi Pembayaran Manual via WhatsApp CS &rarr;</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Why Choose Us / Keunggulan Teknologi -->
    <section id="fitur" class="scroll-mt-20 py-20 relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-16">
                <span class="inline-flex items-center gap-1.5 text-xs font-extrabold text-[#00e07e] uppercase tracking-wider px-3.5 py-1.5 rounded-full bg-[#0f3a25] border border-[#00e07e]/30 shadow-sm shadow-[#00e07e]/10">
                    <span class="material-symbols-rounded text-sm">verified</span>
                    Kualitas Terbaik &amp; Handal
                </span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-[#eef1f0] mt-3 tracking-tight">
                    Mengapa Memilih {{ $companyName }}?
                </h2>
                <p class="text-sm sm:text-base text-[#9aa49f] mt-2">
                    Kami menghadirkan pengalaman berinternet kelas enterprise dengan harga ramah untuk seluruh keluarga dan bisnis Anda.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-7">
                <!-- Feature 1: Broadband -->
                <div class="group relative rounded-2xl p-7 bg-[#131615] border border-[#232826] hover:border-[#00e07e]/60 transition-all duration-300 hover:shadow-[0_12px_36px_-6px_rgba(0,224,126,0.18)] hover:-translate-y-1.5 flex flex-col justify-between overflow-hidden">
                    <div class="absolute -top-10 -right-10 w-28 h-28 bg-[#00e07e]/10 rounded-full blur-2xl group-hover:bg-[#00e07e]/25 transition-all"></div>
                    <div>
                        <div class="flex items-center justify-between mb-5">
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#0f3a25] to-[#1a1e1c] text-[#00e07e] flex items-center justify-center border border-[#00e07e]/30 shadow-md shadow-[#00e07e]/15 group-hover:scale-105 group-hover:border-[#00e07e] transition-all">
                                <span class="material-symbols-rounded text-2xl text-[#00e07e]">wifi</span>
                            </div>
                            <span class="text-[10px] font-extrabold uppercase tracking-wider px-2.5 py-1 rounded-full bg-[#1a1e1c] text-[#00e07e] border border-[#232826]">
                                Cepat &amp; Stabil
                            </span>
                        </div>
                        <h3 class="text-lg font-bold text-[#eef1f0] mb-2 group-hover:text-white transition-colors">
                            Koneksi Cepat &amp; Stabil
                        </h3>
                        <p class="text-xs sm:text-sm text-[#9aa49f] leading-relaxed">
                            Akses internet berkualitas tinggi langsung ke rumah Anda dengan latensi rendah, koneksi stabil 24 jam untuk kebutuhan streaming, browsing, dan kerja remote.
                        </p>
                    </div>
                    <div class="mt-6 pt-4 border-t border-[#232826] flex items-center gap-2 text-xs font-semibold text-[#00e07e]">
                        <span class="material-symbols-rounded text-sm">check_circle</span>
                        <span>Jalur Dedicated Anti-Drop</span>
                    </div>
                    <div class="absolute -right-2 -bottom-2 w-16 opacity-30 group-hover:opacity-90 group-hover:scale-105 transition-all pointer-events-none drop-shadow-[0_4px_12px_rgba(0,224,126,0.3)]">
                        <img src="{{ asset('images/mascot-streaming.webp') }}" alt="Mascot Streaming" class="w-full h-auto object-contain">
                    </div>
                </div>

                <!-- Feature 2: Gaming -->
                <div class="group relative rounded-2xl p-7 bg-[#131615] border border-[#232826] hover:border-[#00e07e]/60 transition-all duration-300 hover:shadow-[0_12px_36px_-6px_rgba(0,224,126,0.18)] hover:-translate-y-1.5 flex flex-col justify-between overflow-hidden">
                    <div class="absolute -top-10 -right-10 w-28 h-28 bg-[#00e07e]/10 rounded-full blur-2xl group-hover:bg-[#00e07e]/25 transition-all"></div>
                    <div>
                        <div class="flex items-center justify-between mb-5">
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#0f3a25] to-[#1a1e1c] text-[#00e07e] flex items-center justify-center border border-[#00e07e]/30 shadow-md shadow-[#00e07e]/15 group-hover:scale-105 group-hover:border-[#00e07e] transition-all">
                                <span class="material-symbols-rounded text-2xl text-[#00e07e]">sports_esports</span>
                            </div>
                            <span class="text-[10px] font-extrabold uppercase tracking-wider px-2.5 py-1 rounded-full bg-[#1a1e1c] text-[#00e07e] border border-[#232826]">
                                Low Latency
                            </span>
                        </div>
                        <h3 class="text-lg font-bold text-[#eef1f0] mb-2 group-hover:text-white transition-colors">
                            Direct Gaming &amp; Peering
                        </h3>
                        <p class="text-xs sm:text-sm text-[#9aa49f] leading-relaxed">
                            Routing direct peering ke IIX, OpenIXP, Google, Cloudflare, YouTube, Steam, dan server game terpopuler untuk ping super rendah bebas lag.
                        </p>
                    </div>
                    <div class="mt-6 pt-4 border-t border-[#232826] flex items-center gap-2 text-xs font-semibold text-[#00e07e]">
                        <span class="material-symbols-rounded text-sm">check_circle</span>
                        <span>Ping Stabil 5 - 15ms</span>
                    </div>
                    <div class="absolute -right-2 -bottom-2 w-16 opacity-30 group-hover:opacity-90 group-hover:scale-105 transition-all pointer-events-none drop-shadow-[0_4px_12px_rgba(0,224,126,0.3)]">
                        <img src="{{ asset('images/mascot-gaming.webp') }}" alt="Mascot Gaming" class="w-full h-auto object-contain">
                    </div>
                </div>

                <!-- Feature 3: FUP -->
                <div class="group relative rounded-2xl p-7 bg-[#131615] border border-[#232826] hover:border-[#00e07e]/60 transition-all duration-300 hover:shadow-[0_12px_36px_-6px_rgba(0,224,126,0.18)] hover:-translate-y-1.5 flex flex-col justify-between overflow-hidden">
                    <div class="absolute -top-10 -right-10 w-28 h-28 bg-[#00e07e]/10 rounded-full blur-2xl group-hover:bg-[#00e07e]/25 transition-all"></div>
                    <div>
                        <div class="flex items-center justify-between mb-5">
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#0f3a25] to-[#1a1e1c] text-[#00e07e] flex items-center justify-center border border-[#00e07e]/30 shadow-md shadow-[#00e07e]/15 group-hover:scale-105 group-hover:border-[#00e07e] transition-all">
                                <span class="material-symbols-rounded text-2xl text-[#00e07e]">speed</span>
                            </div>
                            <span class="text-[10px] font-extrabold uppercase tracking-wider px-2.5 py-1 rounded-full bg-[#1a1e1c] text-[#00e07e] border border-[#232826]">
                                Transparan
                            </span>
                        </div>
                        <h3 class="text-lg font-bold text-[#eef1f0] mb-2 group-hover:text-white transition-colors">
                            FUP Adil &amp; Alert WhatsApp
                        </h3>
                        <p class="text-xs sm:text-sm text-[#9aa49f] leading-relaxed">
                            Kebijakan pemakaian wajar yang jujur dan transparan dengan pemberitahuan otomatis ke nomor WhatsApp pelanggan jika kuota mendekati batas.
                        </p>
                    </div>
                    <div class="mt-6 pt-4 border-t border-[#232826] flex items-center gap-2 text-xs font-semibold text-[#00e07e]">
                        <span class="material-symbols-rounded text-sm">check_circle</span>
                        <span>Auto-Warning via WhatsApp</span>
                    </div>
                </div>

                <!-- Feature 4: Hotspot -->
                <div class="group relative rounded-2xl p-7 bg-[#131615] border border-[#232826] hover:border-[#00e07e]/60 transition-all duration-300 hover:shadow-[0_12px_36px_-6px_rgba(0,224,126,0.18)] hover:-translate-y-1.5 flex flex-col justify-between overflow-hidden">
                    <div class="absolute -top-10 -right-10 w-28 h-28 bg-[#00e07e]/10 rounded-full blur-2xl group-hover:bg-[#00e07e]/25 transition-all"></div>
                    <div>
                        <div class="flex items-center justify-between mb-5">
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#0f3a25] to-[#1a1e1c] text-[#00e07e] flex items-center justify-center border border-[#00e07e]/30 shadow-md shadow-[#00e07e]/15 group-hover:scale-105 group-hover:border-[#00e07e] transition-all">
                                <span class="material-symbols-rounded text-2xl text-[#00e07e]">wifi_tethering</span>
                            </div>
                            <span class="text-[10px] font-extrabold uppercase tracking-wider px-2.5 py-1 rounded-full bg-[#1a1e1c] text-[#00e07e] border border-[#232826]">
                                Multi Device
                            </span>
                        </div>
                        <h3 class="text-lg font-bold text-[#eef1f0] mb-2 group-hover:text-white transition-colors">
                            Hotspot Voucher Multi-Device
                        </h3>
                        <p class="text-xs sm:text-sm text-[#9aa49f] leading-relaxed">
                            Mendukung fitur <em>Shared Users</em> hingga 3 perangkat secara simultan dengan 1 kode voucher. Solusi sempurna untuk warkop, kafe, dan kos-kosan.
                        </p>
                    </div>
                    <div class="mt-6 pt-4 border-t border-[#232826] flex items-center gap-2 text-xs font-semibold text-[#00e07e]">
                        <span class="material-symbols-rounded text-sm">check_circle</span>
                        <span>1 Voucher untuk 3 Gadget</span>
                    </div>
                </div>

                <!-- Feature 5: QRIS -->
                <div class="group relative rounded-2xl p-7 bg-[#131615] border border-[#232826] hover:border-[#00e07e]/60 transition-all duration-300 hover:shadow-[0_12px_36px_-6px_rgba(0,224,126,0.18)] hover:-translate-y-1.5 flex flex-col justify-between overflow-hidden">
                    <div class="absolute -top-10 -right-10 w-28 h-28 bg-[#00e07e]/10 rounded-full blur-2xl group-hover:bg-[#00e07e]/25 transition-all"></div>
                    <div>
                        <div class="flex items-center justify-between mb-5">
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#0f3a25] to-[#1a1e1c] text-[#00e07e] flex items-center justify-center border border-[#00e07e]/30 shadow-md shadow-[#00e07e]/15 group-hover:scale-105 group-hover:border-[#00e07e] transition-all">
                                <span class="material-symbols-rounded text-2xl text-[#00e07e]">qr_code_scanner</span>
                            </div>
                            <span class="text-[10px] font-extrabold uppercase tracking-wider px-2.5 py-1 rounded-full bg-[#1a1e1c] text-[#00e07e] border border-[#232826]">
                                QRIS Otomatis
                            </span>
                        </div>
                        <h3 class="text-lg font-bold text-[#eef1f0] mb-2 group-hover:text-white transition-colors">
                            Pembayaran Mandiri QRIS
                        </h3>
                        <p class="text-xs sm:text-sm text-[#9aa49f] leading-relaxed">
                            Bayar tagihan bulanan kapan saja langsung dari smartphone dengan Dynamic QRIS di portal pelanggan. Konfirmasi &amp; aktivasi otomatis tanpa repot transfer manual.
                        </p>
                    </div>
                    <div class="mt-6 pt-4 border-t border-[#232826] flex items-center gap-2 text-xs font-semibold text-[#00e07e]">
                        <span class="material-symbols-rounded text-sm">check_circle</span>
                        <span>Buka Isolir Instan &lt; 5 Detik</span>
                    </div>
                    <div class="absolute -right-2 -bottom-2 w-16 opacity-30 group-hover:opacity-90 group-hover:scale-105 transition-all pointer-events-none drop-shadow-[0_4px_12px_rgba(0,224,126,0.3)]">
                        <img src="{{ asset('images/mascot-wifi-phone.webp') }}" alt="Mascot Mobile" class="w-full h-auto object-contain">
                    </div>
                </div>

                <!-- Feature 6: NOC -->
                <div class="group relative rounded-2xl p-7 bg-[#131615] border border-[#232826] hover:border-[#00e07e]/60 transition-all duration-300 hover:shadow-[0_12px_36px_-6px_rgba(0,224,126,0.18)] hover:-translate-y-1.5 flex flex-col justify-between overflow-hidden">
                    <div class="absolute -top-10 -right-10 w-28 h-28 bg-[#00e07e]/10 rounded-full blur-2xl group-hover:bg-[#00e07e]/25 transition-all"></div>
                    <div>
                        <div class="flex items-center justify-between mb-5">
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#0f3a25] to-[#1a1e1c] text-[#00e07e] flex items-center justify-center border border-[#00e07e]/30 shadow-md shadow-[#00e07e]/15 group-hover:scale-105 group-hover:border-[#00e07e] transition-all">
                                <span class="material-symbols-rounded text-2xl text-[#00e07e]">support_agent</span>
                            </div>
                            <span class="text-[10px] font-extrabold uppercase tracking-wider px-2.5 py-1 rounded-full bg-[#1a1e1c] text-[#00e07e] border border-[#232826]">
                                NOC &amp; Helpdesk
                            </span>
                        </div>
                        <h3 class="text-lg font-bold text-[#eef1f0] mb-2 group-hover:text-white transition-colors">
                            Teknisi &amp; NOC Siap 24/7
                        </h3>
                        <p class="text-xs sm:text-sm text-[#9aa49f] leading-relaxed">
                            Monitoring jaringan MikroTik &amp; Access Point real-time dengan tim lapangan yang siap terjun menangani gangguan teknis dengan waktu tanggap cepat.
                        </p>
                    </div>
                    <div class="mt-6 pt-4 border-t border-[#232826] flex items-center gap-2 text-xs font-semibold text-[#00e07e]">
                        <span class="material-symbols-rounded text-sm">check_circle</span>
                        <span>Tiket &amp; Tracking Lapangan</span>
                    </div>
                    <div class="absolute -right-2 -bottom-2 w-16 opacity-30 group-hover:opacity-90 group-hover:scale-105 transition-all pointer-events-none drop-shadow-[0_4px_12px_rgba(0,224,126,0.3)]">
                        <img src="{{ asset('images/mascot-technician.webp') }}" alt="Mascot Teknisi" class="w-full h-auto object-contain">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Packages Section -->
    <section id="paket" class="scroll-mt-20 py-20 bg-[#0a0c0b] border-t border-[#232826]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-12">
                <span class="inline-flex items-center gap-1.5 text-xs font-extrabold text-[#00e07e] uppercase tracking-wider px-3.5 py-1.5 rounded-full bg-[#0f3a25] border border-[#00e07e]/30 shadow-sm shadow-[#00e07e]/10">
                    <span class="material-symbols-rounded text-sm">tune</span>
                    Pilihan Paket Fleksibel
                </span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-[#eef1f0] mt-3">
                    Paket Internet Sesuai Kebutuhan Anda
                </h2>
                <p class="text-sm sm:text-base text-[#9aa49f] mt-2">
                    Tersedia paket langganan bulanan tanpa kuota ribet hingga voucher hotspot harian ramah di kantong.
                </p>

                <!-- Pricing Tabs -->
                <div class="inline-flex items-center gap-1.5 p-1.5 rounded-xl bg-[#1a1e1c] border border-[#232826] mt-8">
                    <button type="button" onclick="switchPackageTab('pppoe')" id="tabBtnPppoe" class="inline-flex items-center gap-1.5 px-5 py-2.5 text-xs font-black rounded-lg transition bg-[#00e07e] text-[#06261a] shadow">
                        <span class="material-symbols-rounded text-sm">home</span>
                        <span>Home Broadband (PPPoE)</span>
                    </button>
                    <button type="button" onclick="switchPackageTab('hotspot')" id="tabBtnHotspot" class="inline-flex items-center gap-1.5 px-5 py-2.5 text-xs font-bold rounded-lg transition text-[#9aa49f] hover:text-[#eef1f0]">
                        <span class="material-symbols-rounded text-sm">confirmation_number</span>
                        <span>Voucher Hotspot Wi-Fi</span>
                    </button>
                </div>
            </div>

            <!-- Tab 1: PPPoE Home Broadband Packages -->
            <div id="tabContentPppoe" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                @forelse ($pppoePackages as $pkg)
                    @php
                        $isPopular = stripos($pkg->name, '20') !== false || stripos($pkg->name, 'ultra') !== false;
                    @endphp
                    <div class="glass-card p-6 flex flex-col justify-between relative @if ($isPopular) border-[#00e07e] shadow-xl shadow-[#00e07e]/10 @endif">
                        @if ($isPopular)
                            <div class="absolute -top-3 left-1/2 transform -translate-x-1/2 px-3 py-0.5 rounded-full bg-[#00e07e] text-[#06261a] text-[10px] font-black tracking-wider uppercase shadow inline-flex items-center gap-1">
                                <span class="material-symbols-rounded text-xs">star</span>
                                <span>Paling Populer</span>
                            </div>
                        @endif

                        <div>
                            @if ($pkg->image)
                                <div class="mb-4 overflow-hidden rounded-xl border border-[#232826] bg-[#1a1e1c]">
                                    <img src="{{ asset('storage/' . $pkg->image) }}" alt="{{ $pkg->name }}" class="h-36 w-full object-cover">
                                </div>
                            @endif
                            <span class="text-xs font-bold text-[#00e07e] uppercase tracking-wider">{{ $pkg->type }}</span>
                            <h3 class="text-xl font-extrabold text-[#eef1f0] mt-1">{{ $pkg->name }}</h3>
                            
                            <!-- Speed Badge -->
                            <div class="my-4 py-2 px-3 rounded-xl bg-[#1a1e1c] border border-[#232826] flex items-center justify-between">
                                <span class="text-xs text-[#9aa49f] font-semibold">Kecepatan:</span>
                                <span class="text-base font-black text-[#00e07e]">{{ $pkg->speed_rate }}</span>
                            </div>

                            <!-- Price -->
                            <div class="mb-6">
                                <span class="text-3xl font-extrabold text-white">Rp {{ number_format($pkg->price, 0, ',', '.') }}</span>
                                <span class="text-xs text-[#9aa49f] font-medium">/ {{ $pkg->validity_days }} Hari</span>
                            </div>

                            <!-- Features List -->
                            <ul class="space-y-2.5 text-xs text-[#9aa49f] pb-6 border-b border-[#232826]">
                                <li class="flex items-center gap-2">
                                    <span class="material-symbols-rounded text-sm text-[#00e07e]">check_circle</span>
                                    <span>Broadband Unlimited 24 Jam</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="material-symbols-rounded text-sm text-[#00e07e]">check_circle</span>
                                    <span>Gratis Peminjaman Router Wi-Fi</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="material-symbols-rounded text-sm text-[#00e07e]">check_circle</span>
                                    @if ($pkg->fup_enabled && $pkg->fup_limit_gb)
                                        <span>Batas FUP: <strong class="text-[#eef1f0]">{{ $pkg->fup_limit_gb }} GB</strong> (Lalu {{ $pkg->fup_throttle_speed }})</span>
                                    @else
                                        <span>Unlimited Tanpa Kuota FUP</span>
                                    @endif
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="material-symbols-rounded text-sm text-[#00e07e]">check_circle</span>
                                    <span>Support Teknisi Lapangan 24/7</span>
                                </li>
                            </ul>
                        </div>

                        <div class="pt-6">
                            <a href="#daftar" onclick="selectPackageForOrder({{ $pkg->id }}, '{{ addslashes($pkg->name) }}')" class="w-full text-center block py-3 rounded-xl text-xs font-black transition @if ($isPopular) bg-[#00e07e] hover:bg-[#4deda4] text-[#06261a] shadow-lg shadow-[#00e07e]/20 @else bg-[#1a1e1c] hover:bg-[#232826] text-[#eef1f0] border border-[#232826] @endif">
                                Pasang Paket Ini
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="col-span-4 text-center py-12 text-[#9aa49f] text-sm">
                        Belum ada paket PPPoE yang aktif.
                    </div>
                @endforelse
            </div>

            <!-- Tab 2: Hotspot Voucher Packages -->
            <div id="tabContentHotspot" class="hidden grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse ($hotspotPackages as $pkg)
                    <div class="glass-card p-6 flex flex-col justify-between border-[#232826] hover:border-[#00e07e]/50">
                        <div>
                            @if ($pkg->image)
                                <div class="mb-4 overflow-hidden rounded-xl border border-[#232826] bg-[#1a1e1c]">
                                    <img src="{{ asset('storage/' . $pkg->image) }}" alt="{{ $pkg->name }}" class="h-32 w-full object-cover">
                                </div>
                            @endif
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-[#00e07e] uppercase tracking-wider">Wi-Fi Hotspot</span>
                                @if ($pkg->shared_users > 1)
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-[#0f3a25] text-[#00e07e] border border-[#00e07e]/30">
                                        <span class="material-symbols-rounded text-xs">devices</span>
                                        <span>{{ $pkg->shared_users }} Perangkat</span>
                                    </span>
                                @endif
                            </div>
                            <h3 class="text-xl font-extrabold text-[#eef1f0] mt-1">{{ $pkg->name }}</h3>

                            <!-- Speed Badge -->
                            <div class="my-4 py-2 px-3 rounded-xl bg-[#1a1e1c] border border-[#232826] flex items-center justify-between">
                                <span class="text-xs text-[#9aa49f] font-semibold">Speed Up To:</span>
                                <span class="text-base font-black text-[#00e07e]">{{ $pkg->speed_rate }}</span>
                            </div>

                            <!-- Price -->
                            <div class="mb-6">
                                <span class="text-3xl font-extrabold text-white">Rp {{ number_format($pkg->price, 0, ',', '.') }}</span>
                                <span class="text-xs text-[#9aa49f] font-medium">/ {{ $pkg->validity_days }} Hari</span>
                            </div>

                            <!-- Features List -->
                            <ul class="space-y-2.5 text-xs text-[#9aa49f] pb-6 border-b border-[#232826]">
                                <li class="flex items-center gap-2">
                                    <span class="material-symbols-rounded text-sm text-[#00e07e]">check_circle</span>
                                    <span>Masa Aktif: <strong class="text-[#eef1f0]">{{ $pkg->validity_days }} Hari</strong></span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="material-symbols-rounded text-sm text-[#00e07e]">check_circle</span>
                                    <span>Shared Users: <strong class="text-[#eef1f0]">{{ $pkg->shared_users ?: 1 }} Perangkat Simultan</strong></span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="material-symbols-rounded text-sm text-[#00e07e]">check_circle</span>
                                    <span>Login Mudah (Kode Voucher Sekali Klik)</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="material-symbols-rounded text-sm text-[#00e07e]">check_circle</span>
                                    <span>Jangkauan Luas di Titik Hotspot Terdaftar</span>
                                </li>
                            </ul>
                        </div>

                        <div class="pt-6">
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $companyPhone) }}?text=Halo%20Admin%20{{ urlencode($companyName) }},%20saya%20mau%20beli%20voucher%20hotspot%20{{ urlencode($pkg->name) }}" target="_blank" class="w-full text-center block py-3 rounded-xl text-xs font-black bg-[#00e07e] hover:bg-[#4deda4] text-[#06261a] transition shadow-lg shadow-[#00e07e]/20">
                                Beli Voucher via WhatsApp
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="col-span-3 text-center py-12 text-[#9aa49f] text-sm">
                        Belum ada paket hotspot voucher aktif.
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- Installation Steps (Alur Pasang) -->
    <section class="py-20 relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-16">
                <span class="text-xs font-extrabold text-[#00e07e] uppercase tracking-wider px-3 py-1 rounded-full bg-[#0f3a25] border border-[#00e07e]/30">
                    Proses Mudah
                </span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-[#eef1f0] mt-3">
                    Cara Pasang Internet Cepat di Rumah
                </h2>
                <p class="text-sm sm:text-base text-[#9aa49f] mt-2">
                    Hanya butuh 4 langkah mudah hingga internet Anda aktif dan siap digunakan.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 relative">
                <!-- Step 1 -->
                <div class="glass-card p-6 text-center relative">
                    <div class="w-12 h-12 rounded-full bg-[#0f3a25] text-[#00e07e] border border-[#00e07e]/40 font-black flex items-center justify-center text-lg mx-auto mb-4">
                        1
                    </div>
                    <h3 class="text-base font-bold text-[#eef1f0] mb-2">Pilih Paket</h3>
                    <p class="text-xs text-[#9aa49f] leading-relaxed">Pilih paket kecepatan sesuai kebutuhan rumah atau bisnis Anda melalui formulir online.</p>
                </div>

                <!-- Step 2 -->
                <div class="glass-card p-6 text-center relative">
                    <div class="w-12 h-12 rounded-full bg-[#0f3a25] text-[#00e07e] border border-[#00e07e]/40 font-black flex items-center justify-center text-lg mx-auto mb-4">
                        2
                    </div>
                    <h3 class="text-base font-bold text-[#eef1f0] mb-2">Survey & Cek Lokasi</h3>
                    <p class="text-xs text-[#9aa49f] leading-relaxed">Tim teknisi mengecek jangkauan sinyal dan jalur instalasi terbaik menuju rumah Anda.</p>
                </div>

                <!-- Step 3 -->
                <div class="glass-card p-6 text-center relative">
                    <div class="w-12 h-12 rounded-full bg-[#0f3a25] text-[#00e07e] border border-[#00e07e]/40 font-black flex items-center justify-center text-lg mx-auto mb-4">
                        3
                    </div>
                    <h3 class="text-base font-bold text-[#eef1f0] mb-2">Pemasangan Router Wi-Fi</h3>
                    <p class="text-xs text-[#9aa49f] leading-relaxed">Instalasi perangkat dan konfigurasi router Wi-Fi oleh teknisi hingga internet langsung terhubung.</p>
                </div>

                <!-- Step 4 -->
                <div class="glass-card p-6 text-center relative">
                    <div class="w-12 h-12 rounded-full bg-[#00e07e] text-[#06261a] font-black flex items-center justify-center text-lg mx-auto mb-4 shadow-lg shadow-[#00e07e]/25">
                        4
                    </div>
                    <h3 class="text-base font-bold text-[#eef1f0] mb-2">Langsung Aktif!</h3>
                    <p class="text-xs text-[#9aa49f] leading-relaxed">Internet langsung aktif dan siap digunakan untuk seluruh keluarga dengan koneksi prima.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Registration Form Section (Lead Gen) -->
    <section id="daftar" class="scroll-mt-20 py-20 bg-[#0a0c0b] border-t border-[#232826] relative">
        <div class="max-w-4xl mx-auto px-4 sm:px-6">
            <div class="text-center mb-10">
                <span class="text-xs font-extrabold text-[#00e07e] uppercase tracking-wider px-3 py-1 rounded-full bg-[#0f3a25] border border-[#00e07e]/30">
                    Formulir Registrasi
                </span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-[#eef1f0] mt-3">
                    Daftar Pasang Baru Sekarang
                </h2>
                <p class="text-sm text-[#9aa49f] mt-2">
                    Isi data di bawah ini, tim customer service dan teknisi lapangan kami akan segera memproses pengajuan Anda.
                </p>
            </div>

            <div class="glass-card p-6 sm:p-10">
                <form id="leadRegisterForm" class="space-y-6">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#eef1f0] mb-2">
                                Nama Lengkap Calon Pelanggan *
                            </label>
                            <input type="text"
                                   name="name"
                                   placeholder="Contoh: Budi Santoso"
                                   required
                                   class="w-full px-4 py-3 rounded-xl bg-[#1a1e1c] border border-[#232826] text-[#eef1f0] placeholder-[#9aa49f] focus:outline-none focus:border-[#00e07e] focus:ring-1 focus:ring-[#00e07e] text-sm transition">
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#eef1f0] mb-2">
                                Nomor WhatsApp Aktif *
                            </label>
                            <input type="tel"
                                   name="phone"
                                   placeholder="Contoh: 081234567890"
                                   required
                                   class="w-full px-4 py-3 rounded-xl bg-[#1a1e1c] border border-[#232826] text-[#eef1f0] placeholder-[#9aa49f] focus:outline-none focus:border-[#00e07e] focus:ring-1 focus:ring-[#00e07e] text-sm transition">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-[#eef1f0] mb-2">
                            Pilihan Paket Internet *
                        </label>
                        <select id="leadPackageSelect"
                                name="package_id"
                                class="w-full px-4 py-3 rounded-xl bg-[#1a1e1c] border border-[#232826] text-[#eef1f0] focus:outline-none focus:border-[#00e07e] focus:ring-1 focus:ring-[#00e07e] text-sm transition">
                            <option value="">-- Pilih Paket Yang Anda Inginkan --</option>
                            <optgroup label="Paket Home Broadband (PPPoE)">
                                @foreach ($pppoePackages as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->speed_rate }}) - Rp {{ number_format($p->price, 0, ',', '.') }}/bln</option>
                                @endforeach
                            </optgroup>
                            <optgroup label="Paket Hotspot Wi-Fi">
                                @foreach ($hotspotPackages as $h)
                                    <option value="{{ $h->id }}">{{ $h->name }} - Rp {{ number_format($h->price, 0, ',', '.') }}</option>
                                @endforeach
                            </optgroup>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-[#eef1f0] mb-2">
                            Alamat Pemasangan Lengkap (Beserta Patokan) *
                        </label>
                        <textarea name="address"
                                  rows="3"
                                  placeholder="Contoh: Jl. Merpati No. 12, RT 02/RW 04, Kelurahan Sukamaju (Dekat Masjid Al-Hidayah)"
                                  required
                                  class="w-full px-4 py-3 rounded-xl bg-[#1a1e1c] border border-[#232826] text-[#eef1f0] placeholder-[#9aa49f] focus:outline-none focus:border-[#00e07e] focus:ring-1 focus:ring-[#00e07e] text-sm transition"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-[#eef1f0] mb-2">
                            Catatan Khusus (Opsional)
                        </label>
                        <input type="text"
                               name="notes"
                               placeholder="Contoh: Tolong survey sore hari setelah jam kerja"
                               class="w-full px-4 py-3 rounded-xl bg-[#1a1e1c] border border-[#232826] text-[#eef1f0] placeholder-[#9aa49f] focus:outline-none focus:border-[#00e07e] focus:ring-1 focus:ring-[#00e07e] text-sm transition">
                    </div>

                    <div id="leadAlert" class="hidden p-4 rounded-xl text-sm"></div>

                    <button type="submit"
                            id="btnSubmitLead"
                            class="w-full py-4 rounded-xl font-black text-sm bg-[#00e07e] hover:bg-[#4deda4] text-[#06261a] shadow-xl shadow-[#00e07e]/20 transition transform hover:-translate-y-0.5 flex items-center justify-center gap-2">
                        <span>Kirim Pengajuan Pasang Baru & Hubungi CS</span>
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="py-20 border-t border-[#232826]">
        <div class="max-w-4xl mx-auto px-4 sm:px-6">
            <div class="text-center mb-12">
                <span class="text-xs font-extrabold text-[#00e07e] uppercase tracking-wider px-3 py-1 rounded-full bg-[#0f3a25] border border-[#00e07e]/30">
                    Bantuan & Informasi
                </span>
                <h2 class="text-3xl font-extrabold text-[#eef1f0] mt-3">
                    Pertanyaan Yang Sering Diajukan (FAQ)
                </h2>
            </div>

            <div class="space-y-4">
                <details class="glass-card p-5 group cursor-pointer">
                    <summary class="font-bold text-[#eef1f0] text-sm sm:text-base flex justify-between items-center list-none">
                        <span>Berapa lama proses pemasangan internet baru setelah mendaftar?</span>
                        <span class="text-[#00e07e] group-open:rotate-180 transition-transform">▼</span>
                    </summary>
                    <p class="text-xs sm:text-sm text-[#9aa49f] mt-3 pt-3 border-t border-[#232826] leading-relaxed">
                        Jika lokasi rumah Anda masuk dalam area jangkauan jaringan kami, teknisi kami dapat melakukan survey dan instalasi dalam kurun waktu 1x24 jam sejak pengajuan disetujui.
                    </p>
                </details>

                <details class="glass-card p-5 group cursor-pointer">
                    <summary class="font-bold text-[#eef1f0] text-sm sm:text-base flex justify-between items-center list-none">
                        <span>Bagaimana cara melakukan pembayaran tagihan bulanan?</span>
                        <span class="text-[#00e07e] group-open:rotate-180 transition-transform">▼</span>
                    </summary>
                    <p class="text-xs sm:text-sm text-[#9aa49f] mt-3 pt-3 border-t border-[#232826] leading-relaxed">
                        Anda dapat mengecek tagihan di menu **Cek Tagihan & QRIS** di atas dan langsung scan kode QRIS menggunakan seluruh bank (BCA, Mandiri, BRI, BNI) atau e-wallet (GoPay, OVO, Dana, ShopeePay). Tagihan akan otomatis terkonfirmasi lunas seketika.
                    </p>
                </details>

                <details class="glass-card p-5 group cursor-pointer">
                    <summary class="font-bold text-[#eef1f0] text-sm sm:text-base flex justify-between items-center list-none">
                        <span>Apakah ada batas pemakaian wajar (FUP) pada paket internet?</span>
                        <span class="text-[#00e07e] group-open:rotate-180 transition-transform">▼</span>
                    </summary>
                    <p class="text-xs sm:text-sm text-[#9aa49f] mt-3 pt-3 border-t border-[#232826] leading-relaxed">
                        Kebijakan FUP kami sangat transparan dan memiliki kuota yang sangat besar (ratusan GB hingga TB). Jika kuota tercapai, internet tetap aktif normal tanpa biaya tambahan, hanya kecepatan yang disesuaikan dan akan otomatis pulih kembali di awal bulan baru.
                    </p>
                </details>

                <details class="glass-card p-5 group cursor-pointer">
                    <summary class="font-bold text-[#eef1f0] text-sm sm:text-base flex justify-between items-center list-none">
                        <span>Apa itu fitur Shared Users pada voucher hotspot?</span>
                        <span class="text-[#00e07e] group-open:rotate-180 transition-transform">▼</span>
                    </summary>
                    <p class="text-xs sm:text-sm text-[#9aa49f] mt-3 pt-3 border-t border-[#232826] leading-relaxed">
                        Fitur Shared Users memungkinkan 1 kode voucher dapat digunakan untuk login bersamaan pada beberapa perangkat (misalnya 3 perangkat sekaligus untuk HP, tablet, dan laptop) tanpa perlu membeli voucher terpisah.
                    </p>
                </details>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer id="kontak" class="scroll-mt-20 bg-[#080a09] border-t border-[#232826] pt-16 pb-12 text-[#9aa49f] text-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-10 pb-12 border-b border-[#232826]">
                <!-- Col 1: About -->
                <div class="space-y-4">
                    <div class="flex items-center gap-2.5">
                        <img src="{{ asset('images/logo.png') }}" alt="{{ $companyName }} Logo" class="w-8 h-8 object-contain drop-shadow-[0_2px_10px_rgba(0,224,126,0.3)]">
                        <span class="text-base font-extrabold text-[#eef1f0]">{{ $companyName }}</span>
                    </div>
                    <p class="text-[#9aa49f] leading-relaxed">
                        Penyelenggara Jasa Telekomunikasi dan Akses Internet Cepat berbasis Jaringan Broadband dan Hotspot Wi-Fi untuk rumah, instansi, dan bisnis.
                    </p>
                </div>

                <!-- Col 2: Fast Links -->
                <div class="space-y-3">
                    <h4 class="text-xs font-bold text-[#eef1f0] uppercase tracking-wider">Tautan Cepat</h4>
                    <ul class="space-y-2">
                        <li><a href="#fitur" class="hover:text-[#00e07e] transition">Keunggulan Layanan</a></li>
                        <li><a href="#paket" class="hover:text-[#00e07e] transition">Paket Home Broadband</a></li>
                        <li><a href="#hotspot" class="hover:text-[#00e07e] transition">Voucher Wi-Fi Hotspot</a></li>
                        <li><a href="#cek-tagihan" class="hover:text-[#00e07e] transition">Cek Tagihan &amp; QRIS</a></li>
                        <li><a href="#daftar" class="hover:text-[#00e07e] transition">Formulir Pasang Baru</a></li>
                    </ul>
                </div>

                <!-- Col 3: Portal Links -->
                <div class="space-y-3">
                    <h4 class="text-xs font-bold text-[#eef1f0] uppercase tracking-wider">Akses Pelanggan</h4>
                    <ul class="space-y-2">
                        <li><a href="{{ route('customer.dashboard') }}" class="hover:text-[#00e07e] transition font-bold text-[#eef1f0] flex items-center gap-1.5"><span class="material-symbols-rounded text-sm text-[#00e07e]">account_circle</span><span>Portal Mandiri Pelanggan</span></a></li>
                        <li><a href="#cek-tagihan" class="hover:text-[#00e07e] transition flex items-center gap-1.5"><span class="material-symbols-rounded text-sm text-[#00e07e]">receipt_long</span><span>Cek Tagihan &amp; QRIS</span></a></li>
                        <li><a href="#daftar" class="hover:text-[#00e07e] transition flex items-center gap-1.5"><span class="material-symbols-rounded text-sm text-[#00e07e]">rocket_launch</span><span>Registrasi Pasang Baru</span></a></li>
                    </ul>
                </div>

                <!-- Col 4: Contact & Office -->
                <div class="space-y-3">
                    <h4 class="text-xs font-bold text-[#eef1f0] uppercase tracking-wider">Kontak &amp; Kantor Pusat</h4>
                    <p class="leading-relaxed text-[#9aa49f] flex items-start gap-1.5">
                        <span class="material-symbols-rounded text-sm text-[#00e07e] shrink-0 mt-0.5">location_on</span>
                        <span>{{ $companyAddress }}</span>
                    </p>
                    <p class="flex items-center gap-1.5">
                        <span class="material-symbols-rounded text-sm text-[#00e07e]">chat</span>
                        <span>WhatsApp: <strong class="text-white">{{ $companyPhone }}</strong></span>
                    </p>
                    <p class="flex items-center gap-1.5">
                        <span class="material-symbols-rounded text-sm text-[#00e07e]">mail</span>
                        <span>Email: <strong class="text-white">{{ $companyEmail }}</strong></span>
                    </p>
                </div>
            </div>

            <!-- Copyright -->
            <div class="pt-8 flex flex-wrap items-center justify-between gap-4 text-[#9aa49f]">
                <p>&copy; {{ date('Y') }} {{ $companyName }}. Hak Cipta Dilindungi Undang-Undang.</p>
                <div class="flex items-center gap-6">
                    <span>High-Speed Network Solution</span>
                    <span>•</span>
                    <span>RouterOS & GenieACS Managed</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript for Mobile Menu, Tab Switching & Bill Checker -->
    <script>
        // 1. Mobile Menu Hamburger Drawer Toggle
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const mobileMenu = document.getElementById('mobileMenu');
        const iconHamburger = document.getElementById('iconHamburger');
        const iconClose = document.getElementById('iconClose');

        function toggleMobileMenu() {
            if (!mobileMenu) return;
            const isHidden = mobileMenu.classList.contains('hidden');
            if (isHidden) {
                mobileMenu.classList.remove('hidden');
                iconHamburger?.classList.add('hidden');
                iconClose?.classList.remove('hidden');
            } else {
                mobileMenu.classList.add('hidden');
                iconHamburger?.classList.remove('hidden');
                iconClose?.classList.add('hidden');
            }
        }

        if (mobileMenuBtn && mobileMenu) {
            mobileMenuBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                toggleMobileMenu();
            });

            // Close when clicking nav links
            document.querySelectorAll('.mobile-nav-link').forEach(link => {
                link.addEventListener('click', () => {
                    mobileMenu.classList.add('hidden');
                    iconHamburger?.classList.remove('hidden');
                    iconClose?.classList.add('hidden');
                });
            });

            // Close when clicking outside
            document.addEventListener('click', (e) => {
                if (!mobileMenu.contains(e.target) && !mobileMenuBtn.contains(e.target) && !mobileMenu.classList.contains('hidden')) {
                    mobileMenu.classList.add('hidden');
                    iconHamburger?.classList.remove('hidden');
                    iconClose?.classList.add('hidden');
                }
            });

            // Close on ESC key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && !mobileMenu.classList.contains('hidden')) {
                    mobileMenu.classList.add('hidden');
                    iconHamburger?.classList.remove('hidden');
                    iconClose?.classList.add('hidden');
                }
            });
        }

        // 2. Package Tab Switching
        function switchPackageTab(tab) {
            const pppoeTab = document.getElementById('tabContentPppoe');
            const hotspotTab = document.getElementById('tabContentHotspot');
            const btnPppoe = document.getElementById('tabBtnPppoe');
            const btnHotspot = document.getElementById('tabBtnHotspot');

            if (tab === 'pppoe') {
                pppoeTab.classList.remove('hidden');
                hotspotTab.classList.add('hidden');
                btnPppoe.className = 'px-5 py-2.5 text-xs font-black rounded-lg transition bg-[#00e07e] text-[#06261a] shadow';
                btnHotspot.className = 'px-5 py-2.5 text-xs font-bold rounded-lg transition text-[#9aa49f] hover:text-[#eef1f0]';
            } else {
                pppoeTab.classList.add('hidden');
                hotspotTab.classList.remove('hidden');
                btnHotspot.className = 'px-5 py-2.5 text-xs font-black rounded-lg transition bg-[#00e07e] text-[#06261a] shadow';
                btnPppoe.className = 'px-5 py-2.5 text-xs font-bold rounded-lg transition text-[#9aa49f] hover:text-[#eef1f0]';
            }
        }

        // 3. Select package helper for lead order
        function selectPackageForOrder(pkgId, pkgName) {
            const selectEl = document.getElementById('leadPackageSelect');
            if (selectEl) {
                selectEl.value = pkgId;
            }
        }

        // 4. Bill Check Ajax Handler
        const billCheckForm = document.getElementById('billCheckForm');
        const btnCheckBill = document.getElementById('btnCheckBill');
        const billAlert = document.getElementById('billAlert');
        const billResultCard = document.getElementById('billResultCard');

        if (billCheckForm) {
            billCheckForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const searchVal = document.getElementById('billSearchInput').value.trim();

                if (!searchVal) return;

                btnCheckBill.disabled = true;
                btnCheckBill.innerHTML = '<span>Memeriksa...</span>';
                billAlert.classList.add('hidden');
                billResultCard.classList.add('hidden');

                try {
                    const response = await fetch('{{ route('landing.check-bill') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ search: searchVal })
                    });

                    const data = await response.json();

                    if (!data.success) {
                        billAlert.className = 'mt-4 p-4 rounded-xl text-xs sm:text-sm bg-rose-500/10 border border-rose-500/20 text-[#f27b7b]';
                        billAlert.innerHTML = `<strong>Peringatan:</strong> ${data.message}`;
                        billAlert.classList.remove('hidden');
                        return;
                    }

                    // Populate Customer Info
                    document.getElementById('resCustName').textContent = data.customer.name;
                    document.getElementById('resCustId').textContent = data.customer.customer_number;
                    document.getElementById('resCustPkg').textContent = data.customer.package_name;

                    const statusBadge = document.getElementById('resBadgeStatus');
                    if (data.customer.status === 'active') {
                        statusBadge.innerHTML = '<span class="px-3 py-1 text-xs font-bold rounded-full bg-[#0f3a25] text-[#00e07e] border border-[#00e07e]/30">Layanan Aktif</span>';
                    } else if (data.customer.status === 'isolated') {
                        statusBadge.innerHTML = '<span class="px-3 py-1 text-xs font-bold rounded-full bg-rose-500/15 text-[#f27b7b] border border-rose-500/30">Layanan Terisolir</span>';
                    } else {
                        statusBadge.innerHTML = `<span class="px-3 py-1 text-xs font-bold rounded-full bg-amber-500/15 text-[#f2c56b] border border-amber-500/30">${data.customer.status}</span>`;
                    }

                    // Bill & QRIS / Status Details
                    const qrisBox = document.getElementById('qrisContainer');
                    const paidBox = document.getElementById('paidContainer');
                    const isolatedBox = document.getElementById('isolatedContainer');
                    const waBtn = document.getElementById('resWaConfirmBtn');
                    const waBtnText = document.getElementById('resWaConfirmText');
                    const dueEl = document.getElementById('resInvDue');

                    qrisBox.classList.add('hidden');
                    paidBox.classList.add('hidden');
                    if (isolatedBox) isolatedBox.classList.add('hidden');

                    const companyName = data.company_name || '{{ $companyName }}';
                    const rawPhone = (data.company_phone || '').replace(/[^0-9]/g, '');
                    const cleanPhone = rawPhone.startsWith('0') ? '62' + rawPhone.substring(1) : rawPhone;

                    if (data.invoice && data.has_unpaid) {
                        const invAmount = Number(data.invoice.amount || data.invoice.total_amount || 0);
                        document.getElementById('resInvNumber').textContent = '#' + data.invoice.invoice_number;
                        document.getElementById('resInvPeriod').textContent = data.invoice.period || 'Bulan Berjalan';
                        dueEl.textContent = data.invoice.due_date || 'Segera';
                        dueEl.className = 'font-bold text-[#f27b7b]';
                        document.getElementById('resInvAmount').textContent = 'Rp ' + invAmount.toLocaleString('id-ID');

                        qrisBox.classList.remove('hidden');

                        const qrisImgBox = document.getElementById('qrisImgBox');
                        const qrCode = data.qris_data_uri || data.qris_qr;
                        if (qrCode) {
                            qrisImgBox.innerHTML = `<img src="${qrCode}" alt="QRIS Dinamis" class="w-48 h-48 object-contain">`;
                        } else {
                            qrisImgBox.innerHTML = '<div class="p-6 text-xs text-[#9aa49f]">QRIS sedang tidak tersedia. Silakan lakukan konfirmasi ke CS WhatsApp di bawah.</div>';
                        }

                        // WhatsApp Confirmation Button
                        const waMsg = encodeURIComponent(`Halo Admin ${companyName}, saya ${data.customer.name} (ID: ${data.customer.customer_number}). Saya ingin konfirmasi pembayaran tagihan internet No. Invoice: ${data.invoice.invoice_number} sebesar Rp ${invAmount.toLocaleString('id-ID')}.`);
                        waBtn.href = data.wa_url || `https://wa.me/${cleanPhone}?text=${waMsg}`;
                        if (waBtnText) waBtnText.textContent = 'Konfirmasi Pembayaran Manual via WhatsApp CS →';
                    } else if (data.customer.status === 'isolated') {
                        // Customer is isolated
                        document.getElementById('resInvNumber').textContent = '-';
                        document.getElementById('resInvPeriod').textContent = '-';
                        dueEl.textContent = 'Terisolir';
                        dueEl.className = 'font-bold text-[#f27b7b]';
                        document.getElementById('resInvAmount').textContent = '-';

                        if (isolatedBox) isolatedBox.classList.remove('hidden');

                        const waMsg = encodeURIComponent(`Halo Admin ${companyName}, saya ${data.customer.name} (ID: ${data.customer.customer_number}). Layanan internet saya saat ini berstatus Terisolir. Mohon info tagihan atau bantuan untuk aktivasi ulang.`);
                        waBtn.href = `https://wa.me/${cleanPhone}?text=${waMsg}`;
                        if (waBtnText) waBtnText.textContent = 'Hubungi CS WhatsApp untuk Buka Isolir →';
                    } else {
                        // Customer is active and has no unpaid invoices
                        document.getElementById('resInvNumber').textContent = '-';
                        document.getElementById('resInvPeriod').textContent = '-';
                        dueEl.textContent = 'Lunas';
                        dueEl.className = 'font-bold text-[#00e07e]';
                        document.getElementById('resInvAmount').textContent = 'Rp 0';

                        paidBox.classList.remove('hidden');

                        const waMsg = encodeURIComponent(`Halo Admin ${companyName}, saya ${data.customer.name} (ID: ${data.customer.customer_number}). Layanan internet saya berjalan normal, terima kasih!`);
                        waBtn.href = `https://wa.me/${cleanPhone}?text=${waMsg}`;
                        if (waBtnText) waBtnText.textContent = 'Hubungi Layanan Bantuan / CS WhatsApp →';
                    }

                    billResultCard.classList.remove('hidden');
                } catch (err) {
                    billAlert.className = 'mt-4 p-4 rounded-xl text-xs sm:text-sm bg-rose-500/10 border border-rose-500/20 text-[#f27b7b]';
                    billAlert.innerHTML = 'Terjadi kesalahan sistem saat mengecek tagihan. Silakan coba sesaat lagi atau hubungi CS.';
                    billAlert.classList.remove('hidden');
                } finally {
                    btnCheckBill.disabled = false;
                    btnCheckBill.innerHTML = '<span class="material-symbols-rounded text-base align-middle mr-1">search</span><span>Cek Tagihan</span>';
                }
            });
        }

        // 5. Lead Registration Form Handler
        const leadForm = document.getElementById('leadRegisterForm');
        const btnSubmitLead = document.getElementById('btnSubmitLead');
        const leadAlert = document.getElementById('leadAlert');

        if (leadForm) {
            leadForm.addEventListener('submit', async (e) => {
                e.preventDefault();

                btnSubmitLead.disabled = true;
                btnSubmitLead.innerHTML = '<span>Mengirim Pendaftaran...</span>';
                leadAlert.classList.add('hidden');

                const formData = new FormData(leadForm);
                const payload = Object.fromEntries(formData.entries());

                try {
                    const response = await fetch('{{ route('landing.register') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    });

                    const data = await response.json();

                    if (!data.success) {
                        leadAlert.className = 'p-4 rounded-xl text-xs sm:text-sm bg-rose-500/10 border border-rose-500/20 text-[#f27b7b]';
                        leadAlert.innerHTML = `<strong>Peringatan:</strong> ${data.message || 'Mohon periksa data formulir Anda kembali.'}`;
                        leadAlert.classList.remove('hidden');
                        return;
                    }

                    leadAlert.className = 'p-4 rounded-xl text-xs sm:text-sm bg-[#0f3a25] border border-[#00e07e]/30 text-[#00e07e] font-semibold';
                    leadAlert.innerHTML = '✅ Pendaftaran berhasil dikirim! Mengalihkan ke WhatsApp Customer Service kami...';
                    leadAlert.classList.remove('hidden');

                    leadForm.reset();

                    if (data.whatsapp_url) {
                        setTimeout(() => {
                            window.open(data.whatsapp_url, '_blank');
                        }, 1200);
                    }
                } catch (err) {
                    leadAlert.className = 'p-4 rounded-xl text-xs sm:text-sm bg-rose-500/10 border border-rose-500/20 text-[#f27b7b]';
                    leadAlert.innerHTML = 'Terjadi kesalahan saat memproses formulir. Silakan coba kembali atau hubungi WhatsApp kami secara langsung.';
                    leadAlert.classList.remove('hidden');
                } finally {
                    btnSubmitLead.disabled = false;
                    btnSubmitLead.innerHTML = '<span>Kirim Pengajuan Pasang Baru & Hubungi CS</span>';
                }
            });
        }

        // 6. Interactive Speedtest Simulator
        let isSpeedtestRunning = false;
        function runInteractiveSpeedtest() {
            if (isSpeedtestRunning) return;
            isSpeedtestRunning = true;

            const btn = document.getElementById('btnRunSpeedTest');
            const icon = document.getElementById('speedtestIcon');
            const btnText = document.getElementById('speedtestBtnText');
            const mainVal = document.getElementById('speedValueMain');
            const dlVal = document.getElementById('speedDownloadVal');
            const ulVal = document.getElementById('speedUploadVal');
            const arc = document.getElementById('speedDialArc');
            const pingVal = document.getElementById('speedPingVal');
            const jitterVal = document.getElementById('speedJitterVal');

            if (btn) btn.classList.add('opacity-75');
            if (icon) icon.classList.add('animate-spin');
            if (btnText) btnText.textContent = 'Mengukur Kecepatan...';

            let step = 0;
            const targetMbps = Math.floor(Math.random() * 8) + 98; // 98 - 105 Mbps
            const targetDl = (targetMbps + (Math.random() * 3.5)).toFixed(1);
            const targetUl = (targetMbps - (Math.random() * 2.2)).toFixed(1);

            // Step 1: Ramp down to 0
            if (mainVal) mainVal.textContent = '0';
            if (dlVal) dlVal.textContent = '0.0';
            if (ulVal) ulVal.textContent = '0.0';
            if (arc) arc.style.strokeDashoffset = '330'; // empty

            const interval = setInterval(() => {
                step++;
                const progress = Math.min(step / 18, 1);
                // Ease out expo
                const ease = progress === 1 ? 1 : 1 - Math.pow(2, -10 * progress);
                const currentVal = Math.round(targetMbps * ease);
                
                if (mainVal) mainVal.textContent = currentVal;
                if (dlVal) dlVal.textContent = (targetDl * ease).toFixed(1);
                if (ulVal && step > 7) {
                    ulVal.textContent = (targetUl * Math.min((step - 7) / 11, 1)).toFixed(1);
                }

                // Arc calculation: offset ranges from 330 (0%) to 60 (100%)
                const currentOffset = 330 - (270 * ease);
                if (arc) arc.style.strokeDashoffset = currentOffset;

                // Ping simulation
                if (pingVal && step === 6) pingVal.textContent = '1 ms';
                if (jitterVal && step === 12) jitterVal.textContent = '0.4 ms';

                if (progress >= 1) {
                    clearInterval(interval);
                    if (mainVal) mainVal.textContent = targetMbps;
                    if (dlVal) dlVal.textContent = targetDl;
                    if (ulVal) ulVal.textContent = targetUl;
                    if (pingVal) pingVal.textContent = (Math.floor(Math.random() * 2) + 1) + ' ms';
                    if (jitterVal) jitterVal.textContent = (Math.random() * 0.4 + 0.4).toFixed(1) + ' ms';
                    
                    if (icon) icon.classList.remove('animate-spin');
                    if (btn) btn.classList.remove('opacity-75');
                    if (btnText) btnText.textContent = '✅ Tes Selesai! Uji Lagi';
                    
                    setTimeout(() => {
                        if (btnText) btnText.textContent = 'Uji Ulang Kecepatan (Speedtest)';
                        isSpeedtestRunning = false;
                    }, 3000);
                }
            }, 75);
        }
    </script>
</body>
</html>
