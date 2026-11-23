<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Login Loket Agen - {{ $companyName ?? 'NetSpeed ISP' }}</title>

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
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg);
            color: var(--text);
            min-height: 100vh;
        }

        .ambient-glow {
            position: fixed;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100vw;
            height: 480px;
            background: radial-gradient(ellipse 60% 50% at 50% -10%, rgba(0, 224, 126, 0.15), transparent);
            pointer-events-none;
            z-index: 0;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 relative antialiased selection:bg-accent selection:text-ink">

    <!-- Ambient Glow Background -->
    <div class="ambient-glow"></div>

    <div class="w-full max-w-md relative z-10 space-y-6">

        <!-- Logo & Branding Header -->
        <div class="text-center space-y-2">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-surface border border-accent/30 shadow-xl shadow-accent/15 mb-2">
                <img src="{{ asset('images/logo.png') }}" alt="Logo" class="w-10 h-10 object-contain drop-shadow-[0_2px_8px_rgba(0,224,126,0.4)]">
            </div>
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-accent/15 border border-accent/30 text-accent text-xs font-bold uppercase tracking-wider">
                <span class="material-symbols-rounded text-sm">storefront</span>
                Portal Loket Kemitraan
            </div>
            <h1 class="text-2xl font-black text-white tracking-tight">
                Masuk ke Loket Agen
            </h1>
            <p class="text-xs text-muted max-w-sm mx-auto">
                Kelola loket penerimaan pembayaran tagihan internet pelanggan PPPoE & pantau saldo deposit secara mandiri.
            </p>
        </div>

        <!-- Login Card -->
        <div class="rounded-2xl bg-surface/90 backdrop-blur-xl border border-border p-6 sm:p-8 shadow-2xl shadow-black/80 space-y-5">

            <!-- Alerts -->
            @if(session('success'))
                <div class="p-3.5 rounded-xl bg-accent/10 border border-accent/30 text-accent text-xs font-semibold flex items-center gap-2">
                    <span class="material-symbols-rounded text-base">check_circle</span>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if($errors->has('login'))
                <div class="p-3.5 rounded-xl bg-danger/10 border border-danger/30 text-danger text-xs font-semibold flex items-center gap-2">
                    <span class="material-symbols-rounded text-base">error</span>
                    <span>{{ $errors->first('login') }}</span>
                </div>
            @endif

            <form action="{{ route('agent.login.submit') }}" method="POST" class="space-y-4">
                @csrf

                <!-- Login input (Email / Phone) -->
                <div class="space-y-1.5">
                    <label for="login" class="block text-xs font-bold text-white uppercase tracking-wider">
                        Email atau Nomor WhatsApp Agen
                    </label>
                    <div class="relative">
                        <span class="material-symbols-rounded absolute left-3.5 top-1/2 -translate-y-1/2 text-muted text-xl">account_circle</span>
                        <input type="text"
                               id="login"
                               name="login"
                               value="{{ old('login') }}"
                               required
                               autofocus
                               placeholder="cth: 081234567890 atau agen@isp.net"
                               class="w-full pl-11 pr-4 py-3 rounded-xl bg-surface2 border border-border text-white text-sm font-semibold placeholder:text-muted/60 focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent transition">
                    </div>
                </div>

                <!-- Password input -->
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label for="password" class="block text-xs font-bold text-white uppercase tracking-wider">
                            Kata Sandi (Password)
                        </label>
                    </div>
                    <div class="relative">
                        <span class="material-symbols-rounded absolute left-3.5 top-1/2 -translate-y-1/2 text-muted text-xl">lock</span>
                        <input type="password"
                               id="password"
                               name="password"
                               required
                               placeholder="Masukkan password Anda"
                               class="w-full pl-11 pr-11 py-3 rounded-xl bg-surface2 border border-border text-white text-sm font-semibold placeholder:text-muted/60 focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent transition">
                        <button type="button"
                                onclick="togglePasswordVisibility()"
                                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-muted hover:text-white transition">
                            <span class="material-symbols-rounded text-xl" id="toggle-eye">visibility</span>
                        </button>
                    </div>
                </div>

                <!-- Remember me -->
                <div class="flex items-center justify-between pt-1">
                    <label class="flex items-center gap-2 cursor-pointer text-xs text-muted select-none">
                        <input type="checkbox" name="remember" class="rounded border-border bg-surface2 text-accent focus:ring-accent focus:ring-offset-bg">
                        <span>Ingat saya di perangkat ini</span>
                    </label>
                </div>

                <!-- Submit Button -->
                <button type="submit"
                        class="w-full py-3.5 rounded-xl bg-accent hover:bg-accent-hover text-ink font-extrabold text-sm shadow-lg shadow-accent/25 transition flex items-center justify-center gap-2 mt-2">
                    <span class="material-symbols-rounded text-xl">login</span>
                    <span>Masuk ke Loket Agen →</span>
                </button>
            </form>
        </div>

        <!-- Footer Help -->
        <div class="text-center text-xs text-muted space-y-1">
            <p>Belum terdaftar sebagai mitra loket resmi?</p>
            <p class="font-semibold text-white">Hubungi Administrator ISP: <span class="text-accent">{{ $companyPhone ?? '0812-3456-7890' }}</span></p>
        </div>

    </div>

    <script>
        function togglePasswordVisibility() {
            const pass = document.getElementById('password');
            const eye = document.getElementById('toggle-eye');
            if (pass.type === 'password') {
                pass.type = 'text';
                eye.innerText = 'visibility_off';
            } else {
                pass.type = 'password';
                eye.innerText = 'visibility';
            }
        }
    </script>
</body>
</html>

