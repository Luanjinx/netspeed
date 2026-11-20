<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Customer & Technician Login for Flutter Mobile App & Web Portal.
     * Supports login using Email, Phone/WhatsApp Number, or Customer ID.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'login' => 'required|string', // Email, No WhatsApp, or ID Pelanggan
            'password' => 'required|string',
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

        // 2. Try finding by Customer table (by email, phone, or customer_number)
        $customer = Customer::where('email', $login)
            ->orWhereIn('phone', $phoneVariants)
            ->orWhere('customer_number', $login)
            ->first();

        // Link user if not found yet
        if (! $user && $customer && $customer->user) {
            $user = $customer->user;
        }

        // Verify password
        $isAuthenticated = false;

        if ($user && Hash::check($password, $user->password)) {
            $isAuthenticated = true;
        } elseif ($customer && filled($customer->password) && Hash::check($password, $customer->password)) {
            // Customer authenticated via direct customer table password
            if (! $user) {
                // Auto create or link User
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
                // Sync user password with customer password
                $user->update(['password' => $customer->password]);
            }
            $isAuthenticated = true;
        }

        if (! $isAuthenticated || ! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Email/No WhatsApp atau password salah.',
            ], 401);
        }

        // Generate Sanctum access token
        $token = $user->createToken('customer-app')->plainTextToken;

        // Ensure customer relation is loaded with package and router
        if (! $customer) {
            $customer = $user->customer()->with(['package', 'router'])->first();
        } else {
            $customer->load(['package', 'router']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->role,
                ],
                'customer' => $customer,
            ],
        ]);
    }

    /**
     * Logout and revoke token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil.',
        ]);
    }
}
