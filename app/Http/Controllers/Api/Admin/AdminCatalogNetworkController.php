<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\GenieacsServer;
use App\Models\InternetPackage;
use App\Models\IspSetting;
use App\Models\Odp;
use App\Models\Promo;
use App\Models\Router;
use App\Services\Genieacs\GenieacsApiService;
use App\Services\Router\MikrotikApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCatalogNetworkController extends Controller
{
    public function __construct(
        protected MikrotikApiService $mikrotikService,
        protected GenieacsApiService $genieacsService
    ) {}

    public function packagesIndex(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => InternetPackage::orderBy('type')->orderBy('price')->get(),
        ]);
    }

    public function packageStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'speed_rate' => 'required|string|max:100',
            'price' => 'required|numeric|min:0',
            'promo_price' => 'nullable|numeric|min:0',
            'promo_cycles' => 'nullable|integer|min:0',
            'validity_days' => 'nullable|integer|min:1',
            'mikrotik_profile' => 'required|string|max:100',
            'mikrotik_isolir_profile' => 'nullable|string|max:100',
            'type' => 'required|string|in:pppoe,hotspot',
            'fup_enabled' => 'nullable|boolean',
            'fup_limit_gb' => 'nullable|integer|min:0',
            'fup_throttle_speed' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:2000',
            'is_active' => 'nullable|boolean',
        ]);

        $pkg = InternetPackage::create([
            ...$validated,
            'validity_days' => $validated['validity_days'] ?? 30,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Paket {$pkg->name} berhasil dibuat.",
            'data' => $pkg,
        ], 201);
    }

    public function packageUpdate(Request $request, int $id): JsonResponse
    {
        $pkg = InternetPackage::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'speed_rate' => 'required|string|max:100',
            'price' => 'required|numeric|min:0',
            'promo_price' => 'nullable|numeric|min:0',
            'promo_cycles' => 'nullable|integer|min:0',
            'validity_days' => 'nullable|integer|min:1',
            'mikrotik_profile' => 'required|string|max:100',
            'mikrotik_isolir_profile' => 'nullable|string|max:100',
            'type' => 'required|string|in:pppoe,hotspot',
            'fup_enabled' => 'nullable|boolean',
            'fup_limit_gb' => 'nullable|integer|min:0',
            'fup_throttle_speed' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:2000',
            'is_active' => 'nullable|boolean',
        ]);

        $pkg->update($validated);

        return response()->json([
            'success' => true,
            'message' => "Paket {$pkg->name} berhasil diperbarui.",
            'data' => $pkg->fresh(),
        ]);
    }

    public function packageDestroy(int $id): JsonResponse
    {
        $pkg = InternetPackage::findOrFail($id);
        $pkg->delete();

        return response()->json(['success' => true, 'message' => 'Paket berhasil dihapus.']);
    }

    public function packagesSyncMikrotik(): JsonResponse
    {
        $res = $this->mikrotikService->syncAllInternetPackagesToRouters();

        return response()->json([
            'success' => ($res['success'] ?? true) !== false,
            'message' => $res['message'] ?? 'Sinkronisasi paket ke router selesai.',
            'data' => $res,
        ]);
    }

    public function promosIndex(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Promo::with('package:id,name')->orderBy('order')->latest()->get(),
        ]);
    }

    public function promoStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'badge_text' => 'nullable|string|max:50',
            'content' => 'nullable|string',
            'offer_title' => 'nullable|string|max:255',
            'offer_duration' => 'nullable|string|max:100',
            'original_price' => 'nullable|numeric|min:0',
            'promo_price' => 'nullable|numeric|min:0',
            'discount_label' => 'nullable|string|max:50',
            'price_unit' => 'nullable|string|max:50',
            'cta_text' => 'nullable|string|max:100',
            'image' => 'nullable|string|max:500',
            'action_type' => 'nullable|string|in:package,whatsapp,link,none',
            'package_id' => 'nullable|integer|exists:internet_packages,id',
            'link_url' => 'nullable|string|max:500',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $promo = Promo::create([
            ...$validated,
            'is_active' => $validated['is_active'] ?? true,
            'order' => $validated['order'] ?? 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Promo berhasil ditambahkan.',
            'data' => $promo,
        ], 201);
    }

    public function promoUpdate(Request $request, int $id): JsonResponse
    {
        $promo = Promo::findOrFail($id);
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'badge_text' => 'nullable|string|max:50',
            'content' => 'nullable|string',
            'offer_title' => 'nullable|string|max:255',
            'offer_duration' => 'nullable|string|max:100',
            'original_price' => 'nullable|numeric|min:0',
            'promo_price' => 'nullable|numeric|min:0',
            'discount_label' => 'nullable|string|max:50',
            'price_unit' => 'nullable|string|max:50',
            'cta_text' => 'nullable|string|max:100',
            'image' => 'nullable|string|max:500',
            'action_type' => 'nullable|string|in:package,whatsapp,link,none',
            'package_id' => 'nullable|integer|exists:internet_packages,id',
            'link_url' => 'nullable|string|max:500',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $promo->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Promo berhasil diperbarui.',
            'data' => $promo->fresh(),
        ]);
    }

    public function promoDestroy(int $id): JsonResponse
    {
        Promo::findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'Promo berhasil dihapus.']);
    }

    public function routersIndex(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Router::withCount('customers')->orderBy('name')->get()->makeHidden('password'),
        ]);
    }

    public function routerStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'host' => 'required|string|max:255',
            'port' => 'nullable|integer|min:1|max:65535',
            'username' => 'required|string|max:100',
            'password' => 'required|string|max:255',
            'use_ssl' => 'nullable|boolean',
            'timeout' => 'nullable|integer|min:1|max:60',
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        $router = Router::create([
            ...$validated,
            'port' => $validated['port'] ?? 8728,
            'timeout' => $validated['timeout'] ?? 5,
            'use_ssl' => $validated['use_ssl'] ?? false,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Router {$router->name} berhasil ditambahkan.",
            'data' => $router->makeHidden('password'),
        ], 201);
    }

    public function routerUpdate(Request $request, int $id): JsonResponse
    {
        $router = Router::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'host' => 'required|string|max:255',
            'port' => 'nullable|integer|min:1|max:65535',
            'username' => 'required|string|max:100',
            'password' => 'nullable|string|max:255',
            'use_ssl' => 'nullable|boolean',
            'timeout' => 'nullable|integer|min:1|max:60',
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        $payload = collect($validated)->except('password')->toArray();
        if (! empty($validated['password'])) {
            $payload['password'] = $validated['password'];
        }
        $router->update($payload);

        return response()->json([
            'success' => true,
            'message' => "Router {$router->name} berhasil diperbarui.",
            'data' => $router->fresh()->makeHidden('password'),
        ]);
    }

    public function routerDestroy(int $id): JsonResponse
    {
        Router::findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'Router berhasil dihapus.']);
    }

    public function routerTestConnection(int $id): JsonResponse
    {
        $router = Router::findOrFail($id);
        $res = $this->mikrotikService->testConnection($router);

        return response()->json([
            'success' => ($res['success'] ?? false) === true,
            'message' => $res['message'] ?? 'Tes koneksi selesai.',
            'data' => $res,
        ]);
    }

    public function routerSyncProfiles(int $id): JsonResponse
    {
        $router = Router::findOrFail($id);
        $res = $this->mikrotikService->syncProfilesFromRouter($router);

        return response()->json([
            'success' => ($res['success'] ?? true) !== false,
            'message' => $res['message'] ?? 'Sinkronisasi profil selesai.',
            'data' => $res,
        ]);
    }

    public function genieacsIndex(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => GenieacsServer::orderBy('name')->get()->makeHidden(['password', 'auth_token']),
        ]);
    }

    public function genieacsStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'base_url' => 'required|url|max:255',
            'username' => 'nullable|string|max:100',
            'password' => 'nullable|string|max:255',
            'auth_token' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        $server = GenieacsServer::create([
            ...$validated,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Server GenieACS {$server->name} ditambahkan.",
            'data' => $server->makeHidden(['password', 'auth_token']),
        ], 201);
    }

    public function genieacsUpdate(Request $request, int $id): JsonResponse
    {
        $server = GenieacsServer::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'base_url' => 'required|url|max:255',
            'username' => 'nullable|string|max:100',
            'password' => 'nullable|string|max:255',
            'auth_token' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        $payload = collect($validated)->except(['password', 'auth_token'])->toArray();
        if (! empty($validated['password'])) {
            $payload['password'] = $validated['password'];
        }
        if (! empty($validated['auth_token'])) {
            $payload['auth_token'] = $validated['auth_token'];
        }
        $server->update($payload);

        return response()->json([
            'success' => true,
            'message' => 'Server GenieACS diperbarui.',
            'data' => $server->fresh()->makeHidden(['password', 'auth_token']),
        ]);
    }

    public function genieacsDestroy(int $id): JsonResponse
    {
        GenieacsServer::findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'Server GenieACS dihapus.']);
    }

    public function genieacsTest(int $id): JsonResponse
    {
        $server = GenieacsServer::findOrFail($id);
        $res = $this->genieacsService->testConnection($server);

        return response()->json([
            'success' => ($res['success'] ?? false) === true,
            'message' => $res['message'] ?? 'Tes koneksi GenieACS selesai.',
            'data' => $res,
        ]);
    }

    public function genieacsDevices(Request $request, int $id): JsonResponse
    {
        $server = GenieacsServer::findOrFail($id);
        $q = $request->query('q');
        $devices = $this->genieacsService->getDevices($server, $q ? trim((string) $q) : null, 40);

        return response()->json([
            'success' => true,
            'data' => $devices,
        ]);
    }

    public function genieacsDeviceAction(Request $request, int $serverId): JsonResponse
    {
        $validated = $request->validate([
            'device_id' => 'required|string',
            'action' => 'required|string|in:refresh,reboot,wifi,factory_reset',
            'ssid' => 'nullable|string|min:2|max:32',
            'password' => 'nullable|string|min:8|max:63',
        ]);

        $server = GenieacsServer::findOrFail($serverId);
        $deviceId = $validated['device_id'];

        $ok = match ($validated['action']) {
            'refresh' => $this->genieacsService->refreshDevice($server, $deviceId),
            'reboot' => $this->genieacsService->rebootDevice($server, $deviceId),
            'factory_reset' => $this->genieacsService->factoryResetDevice($server, $deviceId),
            'wifi' => $this->genieacsService->setWifiSettings(
                $server,
                $deviceId,
                (string) ($validated['ssid'] ?? ''),
                $validated['password'] ?? null
            ),
        };

        return response()->json([
            'success' => $ok,
            'message' => $ok ? "Perintah {$validated['action']} berhasil dikirim ke ONT." : "Gagal menjalankan {$validated['action']}.",
        ]);
    }

    public function odpsIndex(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Odp::withCount('customers')->orderBy('name')->get(),
        ]);
    }

    public function odpStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:odps,code',
            'total_ports' => 'required|integer|min:1',
            'splitter_ratio' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'status' => 'nullable|string|in:active,full,maintenance',
            'notes' => 'nullable|string|max:500',
        ]);

        $odp = Odp::create([
            ...$validated,
            'status' => $validated['status'] ?? 'active',
        ]);

        return response()->json([
            'success' => true,
            'message' => "ODP {$odp->name} berhasil dibuat.",
            'data' => $odp,
        ], 201);
    }

    public function odpUpdate(Request $request, int $id): JsonResponse
    {
        $odp = Odp::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:odps,code,'.$odp->id,
            'total_ports' => 'required|integer|min:1',
            'splitter_ratio' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'status' => 'nullable|string|in:active,full,maintenance',
            'notes' => 'nullable|string|max:500',
        ]);

        $odp->update($validated);

        return response()->json([
            'success' => true,
            'message' => "ODP {$odp->name} diperbarui.",
            'data' => $odp->fresh(),
        ]);
    }

    public function odpDestroy(int $id): JsonResponse
    {
        Odp::findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'ODP berhasil dihapus.']);
    }

    public function networkMapData(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'office' => IspSetting::getOfficeLocation(),
                'odps' => Odp::withCount('customers')->get(['id', 'name', 'code', 'total_ports', 'latitude', 'longitude', 'status', 'address']),
                'customers' => Customer::whereNotNull('latitude')
                    ->whereNotNull('longitude')
                    ->get(['id', 'customer_number', 'name', 'status', 'latitude', 'longitude', 'odp_id', 'address']),
            ],
        ]);
    }
}
