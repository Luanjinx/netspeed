<?php

namespace App\Http\Middleware;

use App\Services\InstallationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfNotInstalled
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('testing')) {
            return $next($request);
        }

        if (! InstallationService::isInstalled()) {
            // Ensure session driver does not crash on missing database tables
            config(['session.driver' => 'file']);

            if (! $request->is('install*') && ! $request->is('up') && ! $request->is('api*')) {
                return redirect()->route('install.step1');
            }
        }

        return $next($request);
    }
}
