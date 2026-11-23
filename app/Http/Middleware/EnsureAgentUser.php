<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAgentUser
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('agent.login');
        }

        if ($user->role !== 'agent' && $user->role !== 'admin') {
            return redirect()->route('agent.login')->withErrors([
                'login' => 'Akses portal khusus untuk akun mitra agen loket pembayaran.',
            ]);
        }

        if (! $user->is_active) {
            auth()->logout();

            return redirect()->route('agent.login')->withErrors([
                'login' => 'Akun agen Anda sedang dinonaktifkan oleh administrator.',
            ]);
        }

        return $next($request);
    }
}
