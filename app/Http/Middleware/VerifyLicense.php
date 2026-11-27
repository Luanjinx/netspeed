<?php

namespace App\Http\Middleware;

use App\Services\InstallationService;
use App\Services\LicenseService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class VerifyLicense
{
    public static bool $enforceInTests = false;

    /**
     * Verify offline Ed25519 signature, grace period heartbeat, expiry, and optional module entitlements.
     */
    public function handle(Request $request, Closure $next, ?string $requiredModule = null): Response
    {
        if (LicenseService::isTestContext() && ! self::$enforceInTests) {
            return $next($request);
        }

        if ($request->is('install*') || $request->is('up') || ! InstallationService::isInstalled()) {
            return $next($request);
        }

        try {
            LicenseService::verifyStored($requiredModule);
        } catch (Throwable $e) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 403);
            }

            abort(403, $e->getMessage());
        }

        return $next($request);
    }
}
