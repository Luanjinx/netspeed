<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate as FilamentAuthenticate;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Http\Request;

class AuthenticateAdminPanel extends FilamentAuthenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  string  ...$guards
     * @return mixed
     */
    public function handle($request, Closure $next, ...$guards)
    {
        $guard = Filament::auth();

        if ($guard->check()) {
            $user = $guard->user();
            $panel = Filament::getCurrentOrDefaultPanel();

            if ($user instanceof FilamentUser && ! $user->canAccessPanel($panel)) {
                // Gracefully redirect non-admin users to their respective portal to avoid blank 403 screens
                if ($user->role === 'agent') {
                    return redirect()->route('agent.dashboard')->with('error', 'Halaman Administrator hanya untuk Admin ISP. Anda dialihkan ke Portal Agen.');
                }

                if ($user->role === 'technician') {
                    return redirect()->route('technician.dashboard')->with('error', 'Halaman Administrator hanya untuk Admin ISP. Anda dialihkan ke Portal Teknisi.');
                }

                if ($user->role === 'customer') {
                    return redirect()->route('customer.dashboard')->with('error', 'Halaman Administrator hanya untuk Admin ISP. Anda dialihkan ke Portal Pelanggan.');
                }

                abort(403, 'Akses Administrator ditolak.');
            }
        }

        return parent::handle($request, $next, ...$guards);
    }
}
