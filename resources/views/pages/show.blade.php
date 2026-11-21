<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $page->title }} - {{ $companyName }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 antialiased min-h-screen flex flex-col justify-between">
    <!-- Navbar -->
    <header class="bg-white border-b border-gray-100 shadow-sm sticky top-0 z-30">
        <div class="max-w-4xl mx-auto px-4 py-4 flex items-center justify-between">
            <a href="{{ url('/') }}" class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-600 font-extrabold text-lg">
                    🚀
                </div>
                <span class="font-extrabold text-lg tracking-tight text-gray-900">{{ $companyName }}</span>
            </a>
            <a href="{{ url('/') }}" class="text-sm font-semibold text-emerald-600 hover:text-emerald-700 transition">
                &larr; Kembali ke Beranda
            </a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-4xl mx-auto px-4 py-10 w-full flex-grow">
        <div class="bg-white rounded-2xl border border-gray-200/80 p-6 md:p-12 shadow-sm">
            <div class="border-b border-gray-100 pb-6 mb-8">
                <span class="inline-block px-3 py-1 bg-emerald-50 text-emerald-700 font-bold text-xs rounded-full uppercase tracking-wider mb-3">
                    Informasi Resmi
                </span>
                <h1 class="text-2xl md:text-4xl font-extrabold text-gray-900 tracking-tight leading-tight">
                    {{ $page->title }}
                </h1>
                <p class="text-xs md:text-sm text-gray-400 mt-2 font-medium">
                    Terakhir diperbarui: {{ $page->updated_at ? $page->updated_at->translatedFormat('d F Y') : date('d F Y') }}
                </p>
            </div>

            <!-- Page Content Body -->
            <div class="prose prose-emerald max-w-none text-gray-700 leading-relaxed text-sm md:text-base space-y-4 whitespace-pre-line">
{{ $page->content }}
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-100 py-6 text-center text-xs text-gray-400">
        <div class="max-w-4xl mx-auto px-4">
            <p>&copy; {{ date('Y') }} {{ $companyName }}. Seluruh hak cipta dilindungi undang-undang.</p>
            <p class="mt-1">Layanan Bantuan: {{ $companyPhone }} &bull; {{ $companyEmail }}</p>
        </div>
    </footer>
</body>
</html>
