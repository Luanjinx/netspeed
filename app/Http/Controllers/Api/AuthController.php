<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\InternetPackage;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\Notification\TelegramService;
use App\Services\Notification\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

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
     * Register a new customer from Flutter Mobile App.
     * Automatically creates User, Customer (pending), and Work Order (SPK) for installation.
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:30',
            'email' => 'nullable|email|max:255',
            'password' => 'required|string|min:6',
            'address' => 'required|string|max:1000',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'package_id' => 'required|exists:internet_packages,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        $rawPhone = trim((string) $request->input('phone'));
        $cleanPhone = preg_replace('/[^0-9]/', '', $rawPhone);
        if (str_starts_with($cleanPhone, '0')) {
            $cleanPhone = '62'.substr($cleanPhone, 1);
        }

        $phoneVariants = [$rawPhone, $cleanPhone, '0'.substr($cleanPhone, 2)];
        if (Customer::whereIn('phone', $phoneVariants)->exists() || User::whereIn('phone', $phoneVariants)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Nomor WhatsApp sudah terdaftar. Silakan login menggunakan nomor ini.',
            ], 422);
        }

        if ($request->filled('email')) {
            $email = trim((string) $request->input('email'));
            if (User::where('email', $email)->exists() || Customer::where('email', $email)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Alamat email sudah terdaftar. Silakan gunakan email lain atau login.',
                ], 422);
            }
        }

        // Generate unique customer number (random numbers only)
        $customerNumber = Customer::generateCustomerNumber();

        $passwordHash = Hash::make($request->input('password'));
        $userEmail = $request->filled('email') ? trim((string) $request->input('email')) : "{$customerNumber}@customer.netspeed";

        // Create User account
        $user = User::create([
            'name' => trim((string) $request->input('name')),
            'email' => $userEmail,
            'phone' => $cleanPhone,
            'password' => $passwordHash,
            'role' => 'customer',
            'is_active' => true,
        ]);

        // Create Customer profile (Status: Pending)
        $customer = Customer::create([
            'customer_number' => $customerNumber,
            'user_id' => $user->id,
            'name' => trim((string) $request->input('name')),
            'phone' => $cleanPhone,
            'email' => $request->filled('email') ? trim((string) $request->input('email')) : null,
            'password' => $passwordHash,
            'address' => trim((string) $request->input('address')),
            'latitude' => $request->input('latitude'),
            'longitude' => $request->input('longitude'),
            'package_id' => $request->input('package_id'),
            'connection_type' => 'pppoe',
            'status' => 'pending',
            'registered_at' => now(),
            'notes' => $request->input('notes'),
        ]);

        // Automatically create Surat Perintah Kerja (Work Order)
        $package = InternetPackage::find($request->input('package_id'));
        $workOrder = WorkOrder::create([
            'type' => 'installation',
            'customer_id' => $customer->id,
            'technician_id' => null,
            'title' => 'Pemasangan Baru - '.$customer->name,
            'description' => "Pendaftaran Pasang Baru via Mobile App.\nPaket: ".($package?->name ?? '-')."\nAlamat: {$customer->address}\nCatatan: ".($customer->notes ?: '-'),
            'status' => 'pending',
            'scheduled_date' => now()->addDays(1),
        ]);

        // Send WhatsApp confirmation to Customer
        try {
            app(WhatsAppService::class)->sendNewRegistration($customer, $workOrder);
        } catch (\Throwable $e) {
            Log::warning('Gagal kirim WhatsApp registrasi baru: '.$e->getMessage());
        }

        // Send Telegram alert to Admin NOC
        try {
            app(TelegramService::class)->notifyNewRegistration($customer, $workOrder);
        } catch (\Throwable $e) {
            Log::warning('Gagal kirim Telegram registrasi baru: '.$e->getMessage());
        }

        // Generate Sanctum access token for instant session
        $token = $user->createToken('customer-app')->plainTextToken;
        $customer->load(['package']);

        return response()->json([
            'success' => true,
            'message' => 'Pendaftaran berhasil! Tim teknisi kami akan segera menghubungi Anda.',
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
                'work_order' => [
                    'order_number' => $workOrder->order_number,
                    'status' => $workOrder->status,
                    'title' => $workOrder->title,
                    'scheduled_date' => $workOrder->scheduled_date?->format('Y-m-d'),
                ],
            ],
        ], 201);
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
