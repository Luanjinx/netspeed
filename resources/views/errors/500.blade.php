<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Terjadi Kesalahan Server | NetSpeed ISP</title>
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
        <!-- 500 Icon -->
        <div class="relative mx-auto w-24 h-24 flex items-center justify-center">
            <div class="absolute inset-0 rounded-full bg-red-500/10 blur-xl"></div>
            <div class="relative w-20 h-20 rounded-2xl bg-zinc-800/80 border border-zinc-700/80 flex items-center justify-center text-red-400 shadow-inner">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <span class="absolute -top-1 -right-1 px-2 py-0.5 rounded-full text-[10px] font-black bg-red-500 text-white tracking-wider uppercase shadow-md">
                500
            </span>
        </div>

        <!-- Title & Description -->
        <div class="space-y-2">
            <div class="inline-block px-3 py-1 bg-red-500/10 border border-red-500/20 text-red-400 text-xs font-semibold rounded-full uppercase tracking-wider">
                Error 500 • Server Error
            </div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Terjadi Kesalahan Server</h1>
            <p class="text-zinc-400 text-sm leading-relaxed">
                Server sedang mengalami kendala sesaat saat memproses permintaan ini. Silakan muat ulang halaman atau hubungi Administrator.
            </p>
        </div>

        <!-- Action Buttons -->
        <div class="space-y-2.5 pt-2">
            <button type="button" onclick="window.location.reload()" class="block w-full py-3 px-4 bg-primary hover:bg-primary-dark text-black font-semibold text-sm rounded-xl transition duration-150 shadow-lg shadow-primary/20">
                Muat Ulang Halaman
            </button>

            <a href="/" class="block w-full py-2.5 px-4 bg-zinc-800/80 hover:bg-zinc-700/80 text-zinc-300 hover:text-white font-medium text-xs rounded-xl border border-zinc-700/60 transition text-center">
                Kembali ke Halaman Utama
            </a>
        </div>
    </div>
</body>
</html>
