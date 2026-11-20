<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\HotspotVoucher;
use App\Models\Invoice;
use App\Models\PushNotification;
use App\Models\Router;
use App\Services\Bandwidth\FupService;
use App\Services\Hotspot\VoucherService;
use App\Services\Notification\OneSignalService;
use App\Services\Router\MikrotikApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminApiController extends Controller
{
    /**
     * Get aggregated NOC dashboard metrics.
     */
    public function dashboard(): JsonResponse
    {
        $currentMonth = now()->format('Y-m');

        // Customer Metrics
        $totalCustomers = Customer::count();
        $activeCustomers = Customer::where('status', 'active')->count();
        $isolatedCustomers = Customer::where('status', 'isolated')->count();

        // Financial Metrics
        $unpaidInvoices = Invoice::where('status', 'unpaid');
        $unpaidAmount = (float) $unpaidInvoices->sum('total_amount');
        $unpaidCount = $unpaidInvoices->count();

        $paidInvoices = Invoice::where('period', $currentMonth)->where('status', 'paid');
        $paidAmount = (float) $paidInvoices->sum('total_amount');
        $paidCount = $paidInvoices->count();

        // Router Status
        $totalRouters = Router::where('is_active', true)->count();
        $onlineRouters = Router::where('is_active', true)->where('status', 'online')->count();

        // Hotspot Stats
        $totalVouchers = HotspotVoucher::count();
        $unusedVouchers = HotspotVoucher::where('status', 'unused')->count();

        // Active Sessions & Live Router Check
        $activePppoeCount = 0;
        $activeHotspotCount = 0;
        $defaultRouter = Router::where('is_active', true)->first();

        if ($defaultRouter) {
            $service = app(MikrotikApiService::class);
            if ($service->connect($defaultRouter)) {
                $pppoeSessions = $service->getPppActive($defaultRouter);
                $hotspotSessions = $service->getHotspotActive($defaultRouter);
                $activePppoeCount = count($pppoeSessions);
                $activeHotspotCount = count($hotspotSessions);
                $service->disconnect();
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'overview' => [
                    'total_customers' => $totalCustomers,
                    'active_customers' => $activeCustomers,
                    'isolated_customers' => $isolatedCustomers,
                    'unpaid_amount' => $unpaidAmount,
                    'unpaid_count' => $unpaidCount,
                    'paid_amount' => $paidAmount,
                    'paid_count' => $paidCount,
                    'total_routers' => $totalRouters,
                    'online_routers' => $onlineRouters,
                    'active_pppoe_sessions' => $activePppoeCount,
                    'active_hotspot_sessions' => $activeHotspotCount,
                    'total_vouchers' => $totalVouchers,
                    'unused_vouchers' => $unusedVouchers,
                ],
                'default_router' => $defaultRouter ? [
                    'id' => $defaultRouter->id,
                    'name' => $defaultRouter->name,
                    'host' => $defaultRouter->host,
                    'port' => $defaultRouter->port,
                    'status' => $defaultRouter->status,
                ] : null,
            ],
        ]);
    }

    /**
     * Get PPPoE data (secrets, online sessions, profiles).
     */
    public function pppoeIndex(Request $request): JsonResponse
    {
        $routerId = $request->query('router_id');
        $router = $routerId ? Router::find($routerId) : Router::where('is_active', true)->first();

        if (! $router) {
            return response()->json([
                'success' => false,
                'message' => 'Router MikroTik tidak ditemukan.',
                'data' => ['secrets' => [], 'active_sessions' => [], 'profiles' => []],
            ]);
        }

        $service = app(MikrotikApiService::class);
        $connected = $service->connect($router);

        $secrets = [];
        $activeSessions = [];
        $profiles = [];

        if ($connected) {
            $secrets = $service->getPppSecrets($router);
            $activeSessions = $service->getPppActive($router);
            $profiles = $service->getPppProfiles($router);
            $service->disconnect();
        }

        // Attach customer mapping
        $customers = Customer::whereNotNull('pppoe_username')->get()->keyBy('pppoe_username');
        foreach ($secrets as &$sec) {
            $uName = $sec['name'] ?? '';
            $sec['is_online'] = in_array($uName, array_column($activeSessions, 'name'));
            $sec['customer'] = $customers[$uName] ?? null;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'router' => [
                    'id' => $router->id,
                    'name' => $router->name,
                    'host' => $router->host,
                    'connected' => $connected,
                ],
                'secrets' => $secrets,
                'active_sessions' => $activeSessions,
                'profiles' => $profiles,
            ],
        ]);
    }

    /**
     * Create new PPPoE secret on MikroTik.
     */
    public function pppoeCreateSecret(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string',
            'password' => 'required|string',
            'profile' => 'required|string',
            'router_id' => 'nullable|integer',
            'remote_address' => 'nullable|string',
            'comment' => 'nullable|string',
        ]);

        $routerId = $request->input('router_id');
        $router = $routerId ? Router::find($routerId) : Router::where('is_active', true)->first();

        if (! $router) {
            return response()->json(['success' => false, 'message' => 'Router tidak ditemukan.'], 404);
        }

        $service = app(MikrotikApiService::class);
        $data = [
            'name' => $request->input('name'),
            'password' => $request->input('password'),
            'profile' => $request->input('profile'),
            'service' => 'pppoe',
            'comment' => $request->input('comment') ?? 'Dibuat dari Admin Flutter App',
        ];

        if ($request->filled('remote_address')) {
            $data['remote-address'] = $request->input('remote_address');
        }

        $synced = $service->syncPppSecret($router, $data);

        return response()->json([
            'success' => $synced,
            'message' => $synced ? 'Secret PPPoE berhasil dibuat di MikroTik.' : 'Gagal menghubungkan ke router MikroTik.',
        ]);
    }

    /**
     * Toggle isolir on PPPoE secret.
     */
    public function pppoeToggleIsolir(Request $request): JsonResponse
    {
        $request->validate([
            'username' => 'required|string',
            'current_profile' => 'required|string',
            'router_id' => 'nullable|integer',
        ]);

        $router = $request->input('router_id') ? Router::find($request->input('router_id')) : Router::where('is_active', true)->first();
        if (! $router) {
            return response()->json(['success' => false, 'message' => 'Router tidak ditemukan.'], 404);
        }

        $username = $request->input('username');
        $currentProfile = $request->input('current_profile');
        $isIsolir = stripos($currentProfile, 'isolir') !== false;

        $targetProfile = $isIsolir ? 'default' : 'PROFIL-ISOLIR';

        $service = app(MikrotikApiService::class);
        $changed = $service->changePppProfile($router, $username, $targetProfile);

        if ($changed && ! $isIsolir) {
            $service->kickPppActive($router, $username);
        }

        return response()->json([
            'success' => $changed,
            'message' => $changed
                ? ($isIsolir ? "Layanan PPPoE {$username} berhasil dibuka isolir." : "User {$username} berhasil diisolir.")
                : 'Gagal mengubah profil secret di MikroTik.',
            'new_profile' => $targetProfile,
        ]);
    }

    /**
     * Kick active PPPoE session.
     */
    public function pppoeKick(Request $request): JsonResponse
    {
        $request->validate([
            'username' => 'required|string',
            'router_id' => 'nullable|integer',
        ]);

        $router = $request->input('router_id') ? Router::find($request->input('router_id')) : Router::where('is_active', true)->first();
        if (! $router) {
            return response()->json(['success' => false, 'message' => 'Router tidak ditemukan.'], 404);
        }

        $service = app(MikrotikApiService::class);
        $kicked = $service->kickPppActive($router, $request->input('username'));

        return response()->json([
            'success' => $kicked,
            'message' => $kicked ? 'Sesi koneksi PPPoE berhasil diputuskan.' : 'Gagal memutuskan sesi PPPoE.',
        ]);
    }

    /**
     * Reset customer FUP usage.
     */
    public function pppoeResetFup(Request $request): JsonResponse
    {
        $request->validate([
            'username' => 'required|string',
        ]);

        $customer = Customer::where('pppoe_username', $request->input('username'))->first();
        if (! $customer) {
            return response()->json(['success' => false, 'message' => 'Data pelanggan tidak ditemukan.'], 404);
        }

        $res = app(FupService::class)->resetCustomerFup($customer);

        return response()->json($res);
    }

    /**
     * Get Hotspot data (vouchers, online sessions, profiles).
     */
    public function hotspotIndex(Request $request): JsonResponse
    {
        $router = $request->query('router_id') ? Router::find($request->query('router_id')) : Router::where('is_active', true)->first();

        $search = $request->query('search');
        $status = $request->query('status');

        $voucherQuery = HotspotVoucher::query();
        if (! empty($search)) {
            $voucherQuery->where('code', 'like', "%{$search}%")
                ->orWhere('generated_batch', 'like', "%{$search}%");
        }
        if (! empty($status)) {
            $voucherQuery->where('status', $status);
        }

        $vouchers = $voucherQuery->latest()->paginate(25);

        $activeSessions = [];
        $profiles = [];

        if ($router) {
            $service = app(MikrotikApiService::class);
            if ($service->connect($router)) {
                $activeSessions = $service->getHotspotActive($router);
                $profiles = $service->getHotspotUserProfiles($router);
                $service->disconnect();
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => [
                    'total' => HotspotVoucher::count(),
                    'unused' => HotspotVoucher::where('status', 'unused')->count(),
                    'used' => HotspotVoucher::where('status', 'used')->count(),
                    'online_devices' => count($activeSessions),
                ],
                'vouchers' => $vouchers,
                'active_sessions' => $activeSessions,
                'profiles' => $profiles,
            ],
        ]);
    }

    /**
     * Generate batch hotspot vouchers.
     */
    public function hotspotGenerate(Request $request): JsonResponse
    {
        $request->validate([
            'profile' => 'required|string',
            'price' => 'required|numeric',
            'validity' => 'required|string',
            'qty' => 'required|integer|min:1|max:500',
            'prefix' => 'nullable|string',
            'code_length' => 'nullable|integer',
            'router_id' => 'nullable|integer',
        ]);

        $router = $request->input('router_id') ? Router::find($request->input('router_id')) : Router::where('is_active', true)->first();
        if (! $router) {
            return response()->json(['success' => false, 'message' => 'Router tidak ditemukan.'], 404);
        }

        $service = app(VoucherService::class);
        $res = $service->generateBatch(
            $router,
            $request->input('profile'),
            (float) $request->input('price'),
            $request->input('validity'),
            (int) $request->input('qty'),
            $request->input('prefix', ''),
            'alphanumeric_upper',
            (int) ($request->input('code_length') ?: 5),
            true,
            null,
            1
        );

        return response()->json([
            'success' => true,
            'message' => "{$res['count']} voucher berhasil digenerate dan didaftarkan ke MikroTik.",
            'batch_id' => $res['batch'],
            'count' => $res['count'],
        ]);
    }

    /**
     * Kick active hotspot session.
     */
    public function hotspotKick(Request $request): JsonResponse
    {
        $request->validate([
            'identifier' => 'required|string', // username or mac
            'router_id' => 'nullable|integer',
        ]);

        $router = $request->input('router_id') ? Router::find($request->input('router_id')) : Router::where('is_active', true)->first();
        if (! $router) {
            return response()->json(['success' => false, 'message' => 'Router tidak ditemukan.'], 404);
        }

        $service = app(MikrotikApiService::class);
        $kicked = $service->kickHotspotActive($router, $request->input('identifier'));

        return response()->json([
            'success' => $kicked,
            'message' => $kicked ? 'Pengguna hotspot berhasil diputuskan.' : 'Gagal memutuskan sesi hotspot.',
        ]);
    }

    /**
     * Delete hotspot voucher.
     */
    public function hotspotDeleteVoucher(int $id): JsonResponse
    {
        $voucher = HotspotVoucher::find($id);
        if (! $voucher) {
            return response()->json(['success' => false, 'message' => 'Voucher tidak ditemukan.'], 404);
        }

        $router = $voucher->router;
        if ($router) {
            app(MikrotikApiService::class)->deleteHotspotUser($router, $voucher->code);
        }

        $voucher->delete();

        return response()->json([
            'success' => true,
            'message' => 'Voucher berhasil dihapus.',
        ]);
    }

    /**
     * List customers for admin.
     */
    public function customers(Request $request): JsonResponse
    {
        $search = $request->query('search');
        $status = $request->query('status');

        $query = Customer::with(['package', 'router', 'odp']);
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('customer_number', 'like', "%{$search}%")
                    ->orWhere('pppoe_username', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        if (! empty($status)) {
            $query->where('status', $status);
        }

        return response()->json([
            'success' => true,
            'data' => $query->latest()->paginate(25),
        ]);
    }

    /**
     * List invoices for admin.
     */
    public function invoices(Request $request): JsonResponse
    {
        $search = $request->query('search');
        $status = $request->query('status');

        $query = Invoice::with(['customer', 'package']);
        if (! empty($search)) {
            $query->where('invoice_number', 'like', "%{$search}%")
                ->orWhereHas('customer', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('customer_number', 'like', "%{$search}%");
                });
        }
        if (! empty($status)) {
            $query->where('status', $status);
        }

        return response()->json([
            'success' => true,
            'data' => $query->latest()->paginate(25),
        ]);
    }

    /**
     * Send OneSignal push notification from mobile admin app.
     */
    public function onesignalSend(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string',
            'message' => 'required|string',
            'target_type' => 'nullable|string', // all, customer, active_customers, isolated_customers
            'customer_id' => 'nullable|integer',
            'action_url' => 'nullable|string',
            'image_url' => 'nullable|string',
        ]);

        $service = app(OneSignalService::class);
        $targetType = $request->input('target_type', 'all');

        $params = [
            'title' => $request->input('title'),
            'message' => $request->input('message'),
            'target_type' => $targetType,
            'action_url' => $request->input('action_url'),
            'image_url' => $request->input('image_url'),
            'data' => [
                'sender' => 'admin_mobile_flutter',
                'sent_at' => now()->toIso8601String(),
            ],
        ];

        if ($targetType === 'customer' && $request->filled('customer_id')) {
            $customer = Customer::find($request->input('customer_id'));
            if ($customer) {
                $params['target_label'] = "{$customer->name} ({$customer->customer_number})";
                $params['external_ids'] = ["customer_{$customer->id}"];
            }
        }

        $res = $service->sendPushNotification($params);

        return response()->json($res);
    }

    /**
     * Get push notification history.
     */
    public function onesignalHistory(): JsonResponse
    {
        $history = PushNotification::latest()->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }
}
