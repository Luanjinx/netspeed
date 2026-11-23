<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\IspSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class CustomerAuthController extends Controller
{
    /**
     * Show Customer Login Form.
     */
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->role === 'technician') {
                return redirect()->route('technician.dashboard');
            }
            if ($user->role === 'agent') {
                return redirect()->route('agent.dashboard');
            }
            if ($user->customer || $user->role === 'customer') {
                return redirect()->route('customer.dashboard');
            }

            return redirect()->intended('/admin');
        }

        $companyName = IspSetting::get('company_name', 'NetSpeed ISP');
        $companyPhone = IspSetting::get('company_phone', '0812-3456-7890');

        return view('customer.login', compact('companyName', 'companyPhone'));
    }

    /**
     * Authenticate Customer via Email, WhatsApp Number, or Customer Number.
     */
    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ], [
            'login.required' => 'Email atau Nomor WhatsApp wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $login = trim((string) $request->input('login'));
        $password = (string) $request->input('password');

        // Phone normalization variants (e.g. 0819... <-> 62819... <-> +62819...)
        $cleanPhone = preg_replace('/[^0-9]/', '', $login);
        $phoneVariants = [$login];
        if (str_starts_with($cleanPhone, '08')) {
            $phoneVariants[] = '62'.substr($cleanPhone, 1);
            $phoneVariants[] = '+62'.substr($cleanPhone, 1);
            $phoneVariants[] = $cleanPhone;
        } elseif (str_starts_with($cleanPhone, '628')) {
            $phoneVariants[] = '0'.substr($cleanPhone, 2);
            $phoneVariants[] = '+'.$cleanPhone;
            $phoneVariants[] = $cleanPhone;
        }

        // 1. Try finding by User table first
        $user = User::where('email', $login)
            ->orWhereIn('phone', $phoneVariants)
            ->first();

        // 2. Try finding by Customer table
        $customer = Customer::where('email', $login)
            ->orWhereIn('phone', $phoneVariants)
            ->orWhere('customer_number', $login)
            ->first();

        // Check user link
        if (! $user && $customer && $customer->user) {
            $user = $customer->user;
        }

        $isAuthenticated = false;

        if ($user && Hash::check($password, $user->password)) {
            $isAuthenticated = true;
        } elseif ($customer && filled($customer->password) && Hash::check($password, $customer->password)) {
            // Customer authenticated via customer table password
            if (! $user) {
                $user = User::updateOrCreate(
                    ['phone' => $customer->phone],
                    [
                        'name' => $customer->name,
                        'email' => $customer->email ?: "{$customer->customer_number}@customer.netspeed",
                        'password' => $customer->password,
                        'role' => 'customer',
                    ]
                );
                $customer->update(['user_id' => $user->id]);
            } else {
                $user->update(['password' => $customer->password]);
            }
            $isAuthenticated = true;
        }

        if (! $isAuthenticated || ! $user) {
            return back()
                ->withErrors(['login' => 'Email / Nomor WhatsApp atau password salah.'])
                ->withInput($request->only('login', 'remember'));
        }

        // Ensure user is linked to customer if customer exists
        if ($customer && ! $customer->user_id) {
            $customer->update(['user_id' => $user->id]);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        if ($user->role === 'technician') {
            return redirect()->route('technician.dashboard')
                ->with('success', "Selamat bertugas, Teknisi {$user->name}!");
        }

        if ($user->role === 'agent') {
            return redirect()->route('agent.dashboard')
                ->with('success', "Selamat datang di Portal Loket Agen, {$user->name}!");
        }

        if ($user->role === 'admin') {
            return redirect()->intended('/admin');
        }

        $greetingName = $customer ? $customer->name : $user->name;

        return redirect()->route('customer.dashboard')
            ->with('success', "Selamat datang kembali, {$greetingName}!");
    }

    /**
     * Customer Logout.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('status', 'Anda telah berhasil keluar dari akun.');
    }
}
