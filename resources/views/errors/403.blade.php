<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Akses Ditolak | NetSpeed ISP</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            DEFAULT: '#00e07e',
                            dark: '#00b865',
                            light: '#4deda4'
                        }
                    }
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-zinc-950 text-zinc-100 min-h-screen flex items-center justify-center p-4 antialiased selection:bg-primary selection:text-black">
    <div class="max-w-md w-full text-center space-y-6 bg-zinc-900/90 border border-zinc-800 p-8 rounded-2xl shadow-2xl backdrop-blur-xl">
        <!-- Icon -->
        <div class="mx-auto w-20 h-20 rounded-full bg-red-500/10 border border-red-500/20 flex items-center justify-center text-red-400">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>

        <!-- Title -->
        <div class="space-y-2">
            <div class="inline-block px-3 py-1 bg-red-500/10 border border-red-500/30 text-red-400 text-xs font-semibold rounded-full uppercase tracking-wider">
                Error 403 • Akses Terlarang
            </div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Akses Ditolak</h1>
            <p class="text-zinc-400 text-sm leading-relaxed">
                Halaman yang Anda tuju memerlukan hak akses khusus (Administrator). Akun Anda saat ini tidak memiliki izin untuk membuka halaman tersebut.
            </p>
        </div>

        @auth
            <!-- Logged in details -->
            <div class="bg-zinc-950/70 border border-zinc-800/80 rounded-xl p-3.5 text-left text-xs space-y-1.5">
                <div class="flex justify-between items-center text-zinc-400">
                    <span>Sedang Login Sebagai:</span>
                    <span class="font-medium text-white truncate max-w-[160px]">{{ auth()->user()->name }}</span>
                </div>
                <div class="flex justify-between items-center text-zinc-400">
                    <span>Peran Akun:</span>
                    <span class="px-2 py-0.5 rounded text-[11px] font-bold uppercase {{ auth()->user()->role === 'agent' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : (auth()->user()->role === 'technician' ? 'bg-blue-500/10 text-blue-400 border border-blue-500/20' : 'bg-zinc-800 text-zinc-300') }}">
                        {{ auth()->user()->role ?? 'User' }}
                    </span>
                </div>
            </div>
        @endauth

        <!-- Action Buttons -->
        <div class="space-y-2.5 pt-2">
            @auth
                @if(auth()->user()->role === 'agent')
                    <a href="{{ route('agent.dashboard') }}" class="block w-full py-3 px-4 bg-emerald-500 hover:bg-emerald-400 text-black font-semibold text-sm rounded-xl transition duration-150 shadow-lg shadow-emerald-500/20">
                        Masuk ke Dashboard Agen
                    </a>
                @elseif(auth()->user()->role === 'technician')
                    <a href="{{ route('technician.dashboard') }}" class="block w-full py-3 px-4 bg-blue-500 hover:bg-blue-400 text-white font-semibold text-sm rounded-xl transition duration-150 shadow-lg shadow-blue-500/20">
                        Masuk ke Dashboard Teknisi
                    </a>
                @elseif(auth()->user()->role === 'customer')
                    <a href="{{ route('customer.dashboard') }}" class="block w-full py-3 px-4 bg-emerald-500 hover:bg-emerald-400 text-black font-semibold text-sm rounded-xl transition duration-150 shadow-lg shadow-emerald-500/20">
                        Masuk ke Dashboard Pelanggan
                    </a>
                @else
                    <a href="/admin" class="block w-full py-3 px-4 bg-primary hover:bg-primary-dark text-black font-semibold text-sm rounded-xl transition duration-150">
                        Buka Panel Admin
                    </a>
                @endif

                <form method="POST" action="{{ route('agent.logout') }}" class="w-full">
                    @csrf
                    <button type="submit" class="w-full py-2.5 px-4 bg-zinc-800 hover:bg-zinc-700 text-zinc-300 hover:text-white font-medium text-xs rounded-xl border border-zinc-700/60 transition">
                        Keluar & Ganti Akun Admin
                    </button>
                </form>
            @else
                <a href="/admin/login" class="block w-full py-3 px-4 bg-primary hover:bg-primary-dark text-black font-semibold text-sm rounded-xl transition duration-150">
                    Login sebagai Administrator
                </a>
            @endauth

            <a href="/" class="inline-block text-xs text-zinc-500 hover:text-zinc-300 transition pt-1">
                &larr; Kembali ke Halaman Utama
            </a>
        </div>
    </div>
</body>
</html>
