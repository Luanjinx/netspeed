<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminApi
{
    /**
     * Restrict API endpoints strictly to active Admin accounts.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->is_active === false || $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Aplikasi ini khusus untuk akun Administrator.',
            ], 403);
        }

        return $next($request);
    }
}
