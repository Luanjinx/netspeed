<?php

namespace App\Http\Middleware;

use App\Models\Customer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomerUser
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('customer.login');
        }

        // Link customer if not linked
        if (! $user->customer) {
            $customer = Customer::where('phone', $user->phone)
                ->orWhere('email', $user->email)
                ->first();

            if ($customer) {
                $customer->update(['user_id' => $user->id]);
            } elseif ($user->role === 'admin' || $user->role === 'technician') {
                return redirect('/admin');
            } else {
                return redirect()->route('customer.login')->withErrors([
                    'login' => 'Akun login ini belum terhubung dengan data pelanggan aktif.',
                ]);
            }
        }

        return $next($request);
    }
}
