<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTechnicianUser
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->role !== 'technician' && $user->role !== 'admin') {
            return redirect()->route('login')->withErrors([
                'login' => 'Akses portal khusus untuk akun teknisi lapangan.',
            ]);
        }

        if (! $user->is_active) {
            auth()->logout();

            return redirect()->route('login')->withErrors([
                'login' => 'Akun teknisi Anda sedang dinonaktifkan oleh administrator.',
            ]);
        }

        return $next($request);
    }
}
