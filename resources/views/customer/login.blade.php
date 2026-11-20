<!DOCTYPE html>
<html lang="id" class="dark scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Pelanggan - {{ $companyName ?? 'NetSpeed ISP' }}</title>

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
            overflow-x: hidden;
        }

        .glass-card {
            background: var(--surface);
            border: 1px solid var(--border);
            box-shadow: var(--shadow), 0 20px 40px -15px rgba(0, 0, 0, 0.7);
            border-radius: 1.25rem;
        }

        .ambient-glow {
            position: absolute;
            width: 450px;
            height: 450px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(0, 224, 126, 0.08) 0%, transparent 70%);
            filter: blur(80px);
            pointer-events: none;
            z-index: 0;
        }

        @keyframes floatMascot {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-8px) rotate(2deg); }
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
            transform: translateY(-1px);
            box-shadow: 0 8px 20px -4px rgba(0, 224, 126, 0.4);
        }
    </style>
</head>
<body class="antialiased flex items-center justify-center p-4 relative selection:bg-[#00e07e] selection:text-[#06261a]">

    <!-- Ambient Glowing Orbs -->
    <div class="ambient-glow -top-20 -left-20"></div>
    <div class="ambient-glow -bottom-20 -right-20"></div>

    <div class="w-full max-w-md relative z-10 py-8">
        <!-- Floating Mascot Wave -->
        <div class="absolute -top-12 -right-4 sm:-right-8 w-24 sm:w-28 z-20 pointer-events-none float-anim filter drop-shadow-[0_10px_25px_rgba(0,224,126,0.35)] select-none">
            <img src="{{ asset('images/mascot-wifi-phone.webp') }}" alt="Mascot NetSpeed" class="w-full h-auto object-contain">
        </div>

        <div class="glass-card p-6 sm:p-8 relative">
            <!-- Brand Logo & Header -->
            <div class="text-center mb-8">
                <a href="{{ url('/') }}" class="inline-flex items-center gap-2.5 group mb-3">
                    <img src="{{ asset('images/logo.png') }}" alt="{{ $companyName ?? 'NetSpeed' }} Logo" class="w-11 h-11 object-contain group-hover:scale-105 transition drop-shadow-[0_4px_16px_rgba(0,224,126,0.35)]">
                    <span class="text-xl font-black tracking-tight text-white">
                        {{ $companyName ?? 'NetSpeed' }}
                    </span>
                </a>
                <h1 class="text-2xl font-extrabold text-white tracking-tight">Portal Pelanggan</h1>
                <p class="text-xs text-[#9aa49f] mt-1">Masuk untuk melihat status internet, tagihan & Wi-Fi</p>
            </div>

            <!-- Flash Status / Error -->
            @if (session('status'))
                <div class="mb-5 p-3 rounded-xl bg-[#00e07e]/10 border border-[#00e07e]/30 text-[#00e07e] text-xs font-semibold text-center">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->has('login'))
                <div class="mb-5 p-3 rounded-xl bg-[#f27b7b]/10 border border-[#f27b7b]/30 text-[#f27b7b] text-xs font-semibold flex items-center gap-2">
                    <span>⚠️</span>
                    <span>{{ $errors->first('login') }}</span>
                </div>
            @endif

            <!-- Form Login -->
            <form method="POST" action="{{ route('customer.login.submit') }}" class="space-y-4">
                @csrf

                <!-- Input Login (Email or WhatsApp) -->
                <div>
                    <label for="loginInput" class="block text-xs font-bold uppercase tracking-wider text-[#9aa49f] mb-1.5">
                        Email atau Nomor WhatsApp
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#9aa49f]">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                            </svg>
                        </div>
                        <input type="text"
                               id="loginInput"
                               name="login"
                               value="{{ old('login') }}"
                               placeholder="cth: 08123456789 atau email@gmail.com"
                               required
                               autofocus
                               class="w-full pl-11 pr-4 py-3 rounded-xl bg-[#1a1e1c] border border-[#232826] text-[#eef1f0] placeholder-[#9aa49f]/60 text-sm font-medium focus:outline-none focus:border-[#00e07e] focus:ring-1 focus:ring-[#00e07e] transition">
                    </div>
                </div>

                <!-- Input Password -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="passwordInput" class="block text-xs font-bold uppercase tracking-wider text-[#9aa49f]">
                            Password
                        </label>
                    </div>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#9aa49f]">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <input type="password"
                               id="passwordInput"
                               name="password"
                               placeholder="Masukkan password akun Anda"
                               required
                               class="w-full pl-11 pr-11 py-3 rounded-xl bg-[#1a1e1c] border border-[#232826] text-[#eef1f0] placeholder-[#9aa49f]/60 text-sm font-medium focus:outline-none focus:border-[#00e07e] focus:ring-1 focus:ring-[#00e07e] transition">
                        <button type="button"
                                id="togglePasswordBtn"
                                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-[#9aa49f] hover:text-white"
                                aria-label="Toggle password visibility">
                            <svg id="eyeIcon" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Remember Me -->
                <div class="flex items-center justify-between text-xs pt-1">
                    <label class="flex items-center gap-2 cursor-pointer text-[#9aa49f] hover:text-white">
                        <input type="checkbox" name="remember" class="w-4 h-4 rounded bg-[#1a1e1c] border-[#232826] text-[#00e07e] focus:ring-0">
                        <span>Ingat saya di perangkat ini</span>
                    </label>
                </div>

                <!-- Submit Button with exact --btn (#00e07e) and --btnText (#06261a) -->
                <button type="submit"
                        class="w-full py-3.5 rounded-xl font-black text-sm btn-primary shadow-lg shadow-[#00e07e]/20 flex items-center justify-center gap-2">
                    <span>Masuk ke Akun</span>
                    <span>→</span>
                </button>
            </form>

            <!-- Quick Links -->
            <div class="mt-6 pt-6 border-t border-[#232826] space-y-3 text-center text-xs text-[#9aa49f]">
                <a href="{{ url('/#cek-tagihan') }}" class="block text-[#00e07e] hover:underline font-semibold">
                    💳 Cek Tagihan Cepat Tanpa Login →
                </a>

                @if(isset($companyPhone) && $companyPhone)
                    <div class="text-[#9aa49f] text-[11px]">
                        Lupa password atau kendala login?
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $companyPhone) }}?text=Halo%20Admin%20NetSpeed,%20saya%20butuh%20bantuan%20login%20portal%20pelanggan" target="_blank" class="text-[#00e07e] hover:underline font-bold">
                            Hubungi CS WhatsApp
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <!-- Back to home -->
        <div class="text-center mt-6">
            <a href="{{ url('/') }}" class="text-xs font-semibold text-[#9aa49f] hover:text-white transition inline-flex items-center gap-1.5">
                <span>←</span>
                <span>Kembali ke Halaman Utama</span>
            </a>
        </div>
    </div>

    <script>
        const passInput = document.getElementById('passwordInput');
        const toggleBtn = document.getElementById('togglePasswordBtn');
        toggleBtn.addEventListener('click', () => {
            if (passInput.type === 'password') {
                passInput.type = 'text';
            } else {
                passInput.type = 'password';
            }
        });
    </script>
</body>
</html>
