<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\IspSetting;
use App\Models\Page;
use App\Models\PaymentGateway;
use App\Models\User;
use App\Services\Notification\TelegramService;
use App\Services\Notification\WhatsAppService;
use App\Services\System\SystemUpdateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminSystemController extends Controller
{
    /**
     * @var array<int, string>
     */
    protected array $allowedSettingKeys = [
        'company_name',
        'company_phone',
        'company_email',
        'company_address',
        'office_lat',
        'office_lng',
        'default_zoom',
        'google_maps_api_key',
        'app_api_base_url',
        'app_api_key',
        'onesignal_enabled',
        'onesignal_app_id',
        'onesignal_rest_api_key',
        'onesignal_notify_invoice',
        'onesignal_notify_payment',
        'onesignal_notify_ticket',
        'fcm_server_key',
        'whatsapp_gateway_url',
        'whatsapp_api_key',
        'whatsapp_sender_number',
        'whatsapp_notify_invoice',
        'whatsapp_notify_reminder',
        'whatsapp_notify_receipt',
        'whatsapp_notify_isolir',
        'whatsapp_notify_fup',
        'telegram_bot_token',
        'telegram_admin_chat_id',
        'telegram_bot_enabled',
        'telegram_notify_payment',
        'telegram_notify_invoice',
        'telegram_notify_ticket',
        'telegram_notify_router',
        'telegram_notify_fup',
        'payment_gateway_provider',
        'payment_auto_activate',
        'payment_auto_receipt',
        'payment_auto_telegram',
        'static_qris_string',
        'use_unique_code',
        'default_isolir_profile',
        'pppoe_local_address',
        'pppoe_pool_range',
        'pppoe_dns_servers',
    ];

    public function staffIndex(Request $request): JsonResponse
    {
        $role = $request->query('role');
        $query = User::whereIn('role', ['admin', 'technician'])->orderBy('role')->orderBy('name');
        if ($role && in_array($role, ['admin', 'technician'], true)) {
            $query->where('role', $role);
        }

        return response()->json([
            'success' => true,
            'data' => $query->get(['id', 'name', 'email', 'phone', 'role', 'work_area', 'technician_code', 'is_active', 'created_at']),
        ]);
    }

    public function staffStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:30',
            'role' => 'required|string|in:admin,technician',
            'password' => 'required|string|min:6',
            'work_area' => 'nullable|string|max:255',
            'technician_code' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
            'work_area' => $validated['work_area'] ?? null,
            'technician_code' => $validated['technician_code'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Akun {$user->role} {$user->name} berhasil dibuat.",
            'data' => $user,
        ], 201);
    }

    public function staffUpdate(Request $request, int $id): JsonResponse
    {
        $user = User::whereIn('role', ['admin', 'technician'])->findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'phone' => 'nullable|string|max:30',
            'role' => 'required|string|in:admin,technician',
            'password' => 'nullable|string|min:6',
            'work_area' => 'nullable|string|max:255',
            'technician_code' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
        ]);

        $payload = collect($validated)->except('password')->toArray();
        if (! empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }
        $user->update($payload);

        return response()->json([
            'success' => true,
            'message' => 'Data akun staf diperbarui.',
            'data' => $user->fresh(),
        ]);
    }

    public function staffDestroy(Request $request, int $id): JsonResponse
    {
        if ($request->user()->id === $id) {
            return response()->json(['success' => false, 'message' => 'Tidak dapat menghapus akun yang sedang digunakan.'], 422);
        }

        $user = User::whereIn('role', ['admin', 'technician'])->findOrFail($id);
        $user->delete();

        return response()->json(['success' => true, 'message' => 'Akun staf dihapus.']);
    }

    public function pagesIndex(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Page::orderBy('order')->orderBy('title')->get(),
        ]);
    }

    public function pageStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:pages,slug',
            'content' => 'required|string',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $page = Page::create([
            ...$validated,
            'order' => $validated['order'] ?? 0,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json(['success' => true, 'message' => 'Halaman dibuat.', 'data' => $page], 201);
    }

    public function pageUpdate(Request $request, int $id): JsonResponse
    {
        $page = Page::findOrFail($id);
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:pages,slug,'.$page->id,
            'content' => 'required|string',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $page->update($validated);

        return response()->json(['success' => true, 'message' => 'Halaman diperbarui.', 'data' => $page->fresh()]);
    }

    public function pageDestroy(int $id): JsonResponse
    {
        Page::findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'Halaman dihapus.']);
    }

    public function gatewaysIndex(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => PaymentGateway::orderBy('sort_order')->get(),
        ]);
    }

    public function gatewayUpdate(Request $request, int $id): JsonResponse
    {
        $gateway = PaymentGateway::findOrFail($id);
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'fee_type' => 'nullable|string|in:flat,percent',
            'fee_flat' => 'nullable|numeric|min:0',
            'fee_percent' => 'nullable|numeric|min:0',
            'instructions' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'config' => 'nullable|array',
        ]);

        $gateway->update($validated);

        return response()->json([
            'success' => true,
            'message' => "Gateway {$gateway->name} diperbarui.",
            'data' => $gateway->fresh(),
        ]);
    }

    public function settingsGet(): JsonResponse
    {
        $values = [];
        foreach ($this->allowedSettingKeys as $key) {
            $values[$key] = IspSetting::get($key, '');
        }

        return response()->json([
            'success' => true,
            'data' => $values,
        ]);
    }

    public function settingsUpdate(Request $request): JsonResponse
    {
        $input = $request->all();
        foreach ($this->allowedSettingKeys as $key) {
            if (array_key_exists($key, $input)) {
                $val = $input[$key];
                if (is_bool($val)) {
                    $val = $val ? '1' : '0';
                }
                IspSetting::set($key, $val !== null ? (string) $val : '');
                if ($key === 'default_isolir_profile') {
                    IspSetting::set('isolir_profile', (string) $val);
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan sistem berhasil disimpan.',
        ]);
    }

    public function testWhatsApp(Request $request, WhatsAppService $whatsAppService): JsonResponse
    {
        $validated = $request->validate([
            'phone' => 'required|string|max:30',
            'message' => 'nullable|string|max:500',
        ]);

        $res = $whatsAppService->sendMessage(
            $validated['phone'],
            $validated['message'] ?? 'Tes koneksi WhatsApp Gateway dari NetSpeed Admin Mobile App.'
        );

        return response()->json([
            'success' => ($res['success'] ?? false) === true,
            'message' => $res['message'] ?? 'Tes pesan WhatsApp diproses.',
        ]);
    }

    public function testTelegram(TelegramService $telegramService): JsonResponse
    {
        $res = $telegramService->sendToAdmin('✅ Tes koneksi Bot Telegram dari NetSpeed Admin Mobile App berhasil!');

        return response()->json([
            'success' => ($res['success'] ?? $res['ok'] ?? false) === true,
            'message' => $res['message'] ?? ($res['description'] ?? 'Tes pesan Telegram diproses.'),
        ]);
    }

    public function systemUpdateStatus(Request $request, SystemUpdateService $updateService): JsonResponse
    {
        $force = $request->boolean('force', false);

        return response()->json([
            'success' => true,
            'data' => [
                'current' => $updateService->getCurrentVersionInfo(),
                'check' => $updateService->checkForUpdates($force),
                'last_history' => $updateService->getLastUpdateHistory(),
            ],
        ]);
    }

    public function systemUpdateRun(Request $request, SystemUpdateService $updateService): JsonResponse
    {
        $res = $updateService->performUpdate([
            'run_migrate' => $request->boolean('run_migrate', true),
        ]);

        return response()->json([
            'success' => ($res['success'] ?? false) === true,
            'message' => $res['message'] ?? 'Proses update sistem selesai.',
            'data' => $res,
        ]);
    }
}
