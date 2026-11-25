<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminOrTechnicianApi
{
    /**
     * Restrict API endpoints to active admin or technician accounts.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->is_active === false || ! in_array($user->role, ['admin', 'technician'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Endpoint ini hanya untuk Admin atau Teknisi.',
            ], 403);
        }

        return $next($request);
    }
}
