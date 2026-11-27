<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Web Installer') - NetSpeed Billing</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                        },
                        obsidian: {
                            950: '#06080F',
                            900: '#0B0F19',
                            800: '#121826',
                            700: '#1F293D',
                        }
                    },
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #06080F;
            background-image: 
                radial-gradient(at 0% 0%, rgba(16, 185, 129, 0.08) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(5, 150, 105, 0.05) 0px, transparent 50%);
            min-height: 100vh;
        }
    </style>
</head>
<body class="text-slate-200 antialiased py-8 px-4 sm:px-6 lg:px-8 flex flex-col justify-between">
    <div class="max-w-4xl mx-auto w-full">
        <!-- Brand Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center gap-3 px-4 py-2 rounded-full bg-obsidian-900 border border-emerald-500/20 shadow-lg shadow-emerald-500/5 mb-3">
                <span class="flex h-3 w-3 relative">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                </span>
                <span class="text-xs font-bold tracking-wider uppercase text-emerald-400">NetSpeed ISP Setup Wizard</span>
            </div>
            <h1 class="text-3xl font-extrabold text-white tracking-tight">Instalasi Sistem Billing ISP</h1>
            <p class="text-slate-400 text-sm mt-1">Konfigurasi cepat lingkungan server, database, dan akun pengelola</p>
        </div>

        <!-- Stepper Navigation -->
        @php
            $steps = [
                1 => ['title' => 'Server & Izin', 'desc' => 'Requirements'],
                2 => ['title' => 'Lisensi', 'desc' => 'Aktivasi Kode'],
                3 => ['title' => 'Database', 'desc' => 'Koneksi & .env'],
                4 => ['title' => 'Migrasi', 'desc' => 'Tabel & Struktur'],
                5 => ['title' => 'Admin & Profil', 'desc' => 'Akun Pengelola'],
                6 => ['title' => 'Selesai', 'desc' => 'Kunci Sistem'],
            ];
            $current = $currentStep ?? 1;
        @endphp
        <div class="mb-8">
            <div class="grid grid-cols-2 sm:grid-cols-6 gap-2 sm:gap-2.5">
                @foreach($steps as $stepNum => $step)
                    @php
                        $isActive = $current === $stepNum;
                        $isPassed = $current > $stepNum;
                    @endphp
                    <div class="p-3 rounded-xl border transition-all duration-200 {{ $isActive ? 'bg-emerald-950/40 border-emerald-500 shadow-md shadow-emerald-500/10' : ($isPassed ? 'bg-obsidian-900/80 border-emerald-500/30 text-emerald-400' : 'bg-obsidian-900/40 border-slate-800 text-slate-500') }}">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold {{ $isActive ? 'bg-emerald-500 text-obsidian-950 ring-2 ring-emerald-400/30' : ($isPassed ? 'bg-emerald-500/20 text-emerald-400' : 'bg-slate-800 text-slate-400') }}">
                                @if($isPassed)
                                    ✓
                                @else
                                    {{ $stepNum }}
                                @endif
                            </span>
                            <span class="text-xs font-semibold {{ $isActive ? 'text-white' : ($isPassed ? 'text-emerald-300' : 'text-slate-400') }}">
                                {{ $step['title'] }}
                            </span>
                        </div>
                        <p class="text-[10px] hidden sm:block {{ $isActive ? 'text-emerald-400' : 'text-slate-500' }}">{{ $step['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Flash Messages -->
        @if(session('success'))
            <div class="mb-6 p-4 rounded-xl bg-emerald-950/60 border border-emerald-500/40 text-emerald-200 text-sm flex items-center gap-3">
                <span class="text-lg">✅</span>
                <div>{{ session('success') }}</div>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 p-4 rounded-xl bg-rose-950/60 border border-rose-500/40 text-rose-200 text-sm flex items-center gap-3">
                <span class="text-lg">⚠️</span>
                <div>{{ session('error') }}</div>
            </div>
        @endif

        @if($errors->any())
            <div class="mb-6 p-4 rounded-xl bg-rose-950/60 border border-rose-500/40 text-rose-200 text-sm">
                <div class="font-bold mb-1 flex items-center gap-2"><span>⚠️</span> Terjadi kesalahan:</div>
                <ul class="list-disc list-inside space-y-1 text-xs">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Main Card Content -->
        <div class="bg-obsidian-900 border border-slate-800/80 rounded-2xl shadow-2xl p-6 sm:p-8 backdrop-blur-xl">
            @yield('content')
        </div>
    </div>

    <!-- Footer -->
    <div class="text-center text-xs text-slate-600 mt-8">
        &copy; {{ date('Y') }} NetSpeed Billing System &bull; High Performance ISP Management
    </div>

    @stack('scripts')
</body>
</html>

