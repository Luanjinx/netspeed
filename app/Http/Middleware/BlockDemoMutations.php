<?php

namespace App\Http\Middleware;

use App\Providers\DemoModeServiceProvider;
use Closure;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockDemoMutations
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! DemoModeServiceProvider::isDemo()) {
            return $next($request);
        }

        // Allow safe read-only methods
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next($request);
        }

        // Allow authentication routes (Login and Logout)
        if ($request->is('*/login*') || $request->is('*/logout*') || $request->is('login*') || $request->is('logout*')) {
            return $next($request);
        }

        // Livewire calls are intercepted cleanly by DemoModeServiceProvider
        // so that form saves can redirect back and dispatch modal events without throwing 422
        if ($request->is('*livewire*')) {
            return $next($request);
        }

        // Check if mutation route
        if ($request->is('admin/*')) {
            // Direct POST/PUT/PATCH/DELETE
            if ($request->wantsJson()) {
                return response()->json([
                    'error' => 'Mode Demo Aktif: Aksi penambahan, perubahan, dan penghapusan data dinonaktifkan.',
                ], 403);
            }

            Notification::make()
                ->warning()
                ->title('⚡ Mode Demo Aktif')
                ->body('Anda sedang menjelajahi akun demonstrasi. Fitur penambahan, perubahan, dan penghapusan data dinonaktifkan (Read-Only).')
                ->duration(5000)
                ->send();

            return back();
        }

        return $next($request);
    }
}
