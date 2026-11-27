<?php

namespace App\Services\Notification;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\IspSetting;
use App\Models\Payment;
use App\Models\PushNotification;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\WorkOrder;
use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OneSignalService
{
    protected string $apiUrl = 'https://onesignal.com/api/v1';

    /**
     * Get configured OneSignal App ID.
     */
    public function getAppId(): string
    {
        return (string) IspSetting::get('onesignal_app_id', '');
    }

    /**
     * Get configured OneSignal REST API Key.
     */
    public function getRestApiKey(): string
    {
        return (string) IspSetting::get('onesignal_rest_api_key', '');
    }

    /**
     * Check if OneSignal is enabled and configured.
     */
    public function isEnabled(): bool
    {
        $enabled = IspSetting::get('onesignal_enabled', '1') === '1';

        return $enabled && ! empty($this->getAppId()) && ! empty($this->getRestApiKey());
    }

    /**
     * Test connection to OneSignal and retrieve app summary info.
     */
    public function testConnection(?string $appId = null, ?string $apiKey = null): array
    {
        $appId = $appId ?: $this->getAppId();
        $apiKey = $apiKey ?: $this->getRestApiKey();

        if (empty($appId) || empty($apiKey)) {
            return [
                'success' => false,
                'message' => 'App ID dan REST API Key OneSignal belum diisi.',
            ];
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => "Key {$apiKey}",
                    'Accept' => 'application/json',
                ])
                ->get("{$this->apiUrl}/apps/{$appId}");

            // Fallback to Basic header if Key header fails with 401
            if ($response->status() === 401) {
                $response = Http::timeout(10)
                    ->withHeaders([
                        'Authorization' => "Basic {$apiKey}",
                        'Accept' => 'application/json',
                    ])
                    ->get("{$this->apiUrl}/apps/{$appId}");
            }

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'message' => 'Koneksi ke OneSignal Berhasil!',
                    'app_name' => $data['name'] ?? 'OneSignal App',
                    'players_count' => $data['players'] ?? 0,
                    'messageable_players' => $data['messageable_players'] ?? 0,
                    'updated_at' => $data['updated_at'] ?? now()->toIso8601String(),
                ];
            }

            $errorMsg = $response->json('errors')[0] ?? "HTTP {$response->status()} - {$response->body()}";

            return [
                'success' => false,
                'message' => "Gagal terhubung ke OneSignal: {$errorMsg}",
            ];
        } catch (Exception $e) {
            Log::error('OneSignal connection test error: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Koneksi error: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Send push notification via OneSignal API.
     *
     * @param  array{
     *     title: string,
     *     message: string,
     *     target_type?: string,
     *     target_label?: string,
     *     external_ids?: array,
     *     player_ids?: array,
     *     action_url?: string|null,
     *     image_url?: string|null,
     *     data?: array
     * }  $params
     */
    public function sendPushNotification(array $params): array
    {
        $appId = $this->getAppId();
        $apiKey = $this->getRestApiKey();

        if (! $this->isEnabled()) {
            return [
                'success' => false,
                'message' => 'Layanan OneSignal belum aktif atau kredensial API belum dikonfigurasi.',
            ];
        }

        $title = trim($params['title'] ?? 'Pemberitahuan ISP');
        $message = trim($params['message'] ?? '');
        $targetType = $params['target_type'] ?? 'all';
        $actionUrl = $params['action_url'] ?? null;
        $imageUrl = $params['image_url'] ?? null;
        $dataPayload = $params['data'] ?? [];

        if (empty($message)) {
            return [
                'success' => false,
                'message' => 'Isi pesan notifikasi tidak boleh kosong.',
            ];
        }

        $payload = [
            'app_id' => $appId,
            'headings' => ['en' => $title],
            'contents' => ['en' => $message],
        ];

        if (! empty($actionUrl)) {
            $payload['url'] = $actionUrl;
        }

        if (! empty($imageUrl)) {
            $payload['big_picture'] = $imageUrl;
            $payload['ios_attachments'] = ['media_id' => $imageUrl];
        }

        if (! empty($dataPayload)) {
            $payload['data'] = $dataPayload;
        }

        // Configure recipient targeting
        $targetLabel = $params['target_label'] ?? 'Semua Pengguna';
        $externalIds = $params['external_ids'] ?? [];
        $subscriptionIds = array_values(array_unique(array_filter(
            array_map('strval', $params['subscription_ids'] ?? [])
        )));

        switch ($targetType) {
            case 'customer':
            case 'user':
            case 'external_user_ids':
                if (! empty($externalIds)) {
                    $stringIds = array_map('strval', $externalIds);
                    $payload['target_channel'] = 'push';
                    $payload['include_aliases'] = ['external_id' => $stringIds];
                } elseif (! empty($subscriptionIds)) {
                    $payload['target_channel'] = 'push';
                    $payload['include_subscription_ids'] = $subscriptionIds;
                }
                break;

            case 'staff_role':
                $role = (string) ($params['role'] ?? 'admin');
                $targetLabel = $params['target_label'] ?? ('Staf '.ucfirst($role));
                if (! empty($externalIds)) {
                    $stringIds = array_map('strval', $externalIds);
                    $payload['target_channel'] = 'push';
                    $payload['include_aliases'] = ['external_id' => $stringIds];
                } else {
                    $payload['filters'] = [
                        ['field' => 'tag', 'key' => 'role', 'relation' => '=', 'value' => $role],
                    ];
                }
                break;

            case 'player_ids':
                if (! empty($params['player_ids'])) {
                    $payload['include_player_ids'] = $params['player_ids'];
                }
                break;

            case 'active_customers':
                $targetLabel = 'Pelanggan Aktif';
                $payload['filters'] = [
                    ['field' => 'tag', 'key' => 'status', 'relation' => '=', 'value' => 'active'],
                ];
                break;

            case 'isolated_customers':
                $targetLabel = 'Pelanggan Diisolir';
                $payload['filters'] = [
                    ['field' => 'tag', 'key' => 'status', 'relation' => '=', 'value' => 'isolated'],
                ];
                break;

            case 'all':
            default:
                $targetLabel = 'Semua Perangkat Pelanggan (Broadcast)';
                $payload['included_segments'] = ['Subscribed Users'];
                break;
        }

        try {
            $response = $this->postNotification($apiKey, $payload);
            $resJson = $response->json() ?? [];

            // Fallback for newer OneSignal User-model apps or unlinked aliases
            if ($this->isNotSubscribedError($resJson)) {
                if ($targetType === 'all') {
                    $retryPayload = $payload;
                    $retryPayload['included_segments'] = ['Total Subscriptions'];
                    $retryResponse = $this->postNotification($apiKey, $retryPayload);
                    $retryJson = $retryResponse->json() ?? [];

                    if ($retryResponse->successful() && ! isset($retryJson['errors'])) {
                        $response = $retryResponse;
                        $resJson = $retryJson;
                    } else {
                        $fallbackSubIds = $this->getStoredSubscriptionIds(null);
                        if (! empty($fallbackSubIds)) {
                            $subPayload = $payload;
                            unset($subPayload['included_segments']);
                            $subPayload['target_channel'] = 'push';
                            $subPayload['include_subscription_ids'] = $fallbackSubIds;
                            $subResponse = $this->postNotification($apiKey, $subPayload);
                            $subJson = $subResponse->json() ?? [];
                            if ($subResponse->successful() && ! isset($subJson['errors'])) {
                                $response = $subResponse;
                                $resJson = $subJson;
                            }
                        }
                    }
                } elseif (in_array($targetType, ['user', 'staff_role'], true)) {
                    if (! empty($subscriptionIds)) {
                        $subPayload = $payload;
                        unset($subPayload['include_aliases'], $subPayload['include_external_user_ids'], $subPayload['filters']);
                        $subPayload['target_channel'] = 'push';
                        $subPayload['include_subscription_ids'] = $subscriptionIds;
                        $subResponse = $this->postNotification($apiKey, $subPayload);
                        $subJson = $subResponse->json() ?? [];
                        if ($subResponse->successful() && ! isset($subJson['errors'])) {
                            $response = $subResponse;
                            $resJson = $subJson;
                        }
                    }
                } elseif (in_array($targetType, ['customer', 'external_user_ids', 'active_customers', 'isolated_customers'], true)) {
                    $fallbackSubIds = ! empty($subscriptionIds)
                        ? $subscriptionIds
                        : $this->getStoredSubscriptionIds(match ($targetType) {
                            'active_customers' => 'active',
                            'isolated_customers' => 'isolated',
                            default => null,
                        });

                    if (! empty($fallbackSubIds)) {
                        $subPayload = $payload;
                        unset($subPayload['include_aliases'], $subPayload['include_external_user_ids'], $subPayload['filters']);
                        $subPayload['target_channel'] = 'push';
                        $subPayload['include_subscription_ids'] = $fallbackSubIds;
                        $subResponse = $this->postNotification($apiKey, $subPayload);
                        $subJson = $subResponse->json() ?? [];
                        if ($subResponse->successful() && ! isset($subJson['errors'])) {
                            $response = $subResponse;
                            $resJson = $subJson;
                        }
                    }
                }
            }

            $isSuccess = $response->successful() && ! isset($resJson['errors']);
            $onesignalId = $resJson['id'] ?? null;
            $recipients = (int) ($resJson['recipients'] ?? 0);

            // Log record into push_notifications table
            $log = PushNotification::create([
                'title' => $title,
                'message' => $message,
                'target_type' => $targetType,
                'target_label' => $targetLabel,
                'external_ids' => $externalIds,
                'image_url' => $imageUrl,
                'action_url' => $actionUrl,
                'data_payload' => $dataPayload,
                'status' => $isSuccess ? 'sent' : 'failed',
                'recipients_count' => $recipients,
                'onesignal_id' => $onesignalId,
                'response_summary' => json_encode($resJson),
            ]);

            if ($isSuccess) {
                return [
                    'success' => true,
                    'message' => "Notifikasi berhasil dikirim ke {$recipients} penerima.",
                    'notification_id' => $onesignalId,
                    'recipients' => $recipients,
                    'log_id' => $log->id,
                ];
            }

            $errorMsg = is_array($resJson['errors'] ?? null)
                ? implode(', ', $resJson['errors'])
                : ($resJson['errors'] ?? "HTTP {$response->status()}");

            if ($this->isNotSubscribedError($resJson)) {
                $errorMsg = 'Belum ada perangkat pelanggan yang terdaftar/berlangganan push notification (All included players are not subscribed). Pastikan APK pelanggan sudah dibuka & mengizinkan notifikasi dengan OneSignal App ID yang sama, serta kredensial Firebase FCM sudah terpasang di Dashboard OneSignal.';
            }

            return [
                'success' => false,
                'message' => "Gagal mengirim notifikasi OneSignal: {$errorMsg}",
                'log_id' => $log->id,
            ];
        } catch (Exception $e) {
            Log::error('OneSignal Send Exception: '.$e->getMessage());

            PushNotification::create([
                'title' => $title,
                'message' => $message,
                'target_type' => $targetType,
                'target_label' => $targetLabel,
                'external_ids' => $externalIds,
                'image_url' => $imageUrl,
                'action_url' => $actionUrl,
                'data_payload' => $dataPayload,
                'status' => 'failed',
                'recipients_count' => 0,
                'response_summary' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan sistem: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Send push notification to a specific customer.
     */
    public function sendToCustomer(Customer $customer, string $title, string $message, ?string $url = null, ?string $image = null, array $data = []): array
    {
        $externalId = "customer_{$customer->id}";
        $data['customer_id'] = $customer->id;

        $params = [
            'title' => $title,
            'message' => $message,
            'target_type' => 'customer',
            'target_label' => "{$customer->name} ({$customer->customer_number})",
            'external_ids' => [$externalId],
            'action_url' => $url,
            'image_url' => $image,
            'data' => $data,
        ];

        if (! empty($customer->onesignal_id)) {
            $params['subscription_ids'] = [$customer->onesignal_id];
        }

        return $this->sendPushNotification($params);
    }

    protected function postNotification(string $apiKey, array $payload): Response
    {
        $response = Http::timeout(12)
            ->withHeaders([
                'Authorization' => "Key {$apiKey}",
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
            ->post("{$this->apiUrl}/notifications", $payload);

        if ($response->status() === 401) {
            return Http::timeout(12)
                ->withHeaders([
                    'Authorization' => "Basic {$apiKey}",
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post("{$this->apiUrl}/notifications", $payload);
        }

        return $response;
    }

    protected function isNotSubscribedError(array $resJson): bool
    {
        $errors = $resJson['errors'] ?? null;
        $text = is_array($errors) ? implode(' ', $errors) : (string) $errors;

        return stripos($text, 'All included players are not subscribed') !== false
            || stripos($text, 'not subscribed') !== false;
    }

    /**
     * Fetch stored OneSignal subscription IDs from Customer records as a resilient fallback.
     *
     * @return list<string>
     */
    protected function getStoredSubscriptionIds(?string $status = null): array
    {
        $query = Customer::query()
            ->whereNotNull('onesignal_id')
            ->where('onesignal_id', '!=', '');

        if ($status !== null) {
            $query->where('status', $status);
        }

        return $query->limit(2000)
            ->pluck('onesignal_id')
            ->map(fn ($id): string => trim((string) $id))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Send broadcast notification to all devices.
     */
    public function sendBroadcast(string $title, string $message, ?string $url = null, ?string $image = null, array $data = []): array
    {
        return $this->sendPushNotification([
            'title' => $title,
            'message' => $message,
            'target_type' => 'all',
            'target_label' => 'Semua Perangkat Pelanggan (Broadcast)',
            'action_url' => $url,
            'image_url' => $image,
            'data' => $data,
        ]);
    }

    /**
     * Trigger push notification when a new invoice is generated.
     */
    public function notifyInvoiceCreated(Invoice $invoice): array
    {
        if (IspSetting::get('onesignal_notify_invoice', '1') !== '1') {
            return ['success' => false, 'message' => 'Otomasi notifikasi tagihan OneSignal dinonaktifkan.'];
        }

        $customer = $invoice->customer;
        if (! $customer) {
            return ['success' => false, 'message' => 'Customer tidak ditemukan.'];
        }

        $formattedAmount = 'Rp '.number_format($invoice->total_amount, 0, ',', '.');
        $dueDate = $invoice->due_date?->format('d M Y') ?? 'Segera';

        $title = '📄 Tagihan Internet Baru Terbit';
        $message = "Halo {$customer->name}, tagihan periode {$invoice->period} sebesar {$formattedAmount} telah terbit. Jatuh tempo: {$dueDate}. Silakan lakukan pembayaran.";

        return $this->sendToCustomer($customer, $title, $message, "/invoices/{$invoice->id}", null, [
            'type' => 'invoice',
            'invoice_id' => $invoice->id,
            'amount' => $invoice->total_amount,
        ]);
    }

    /**
     * Trigger push notification when payment is confirmed.
     */
    public function notifyPaymentReceived(Payment $payment): array
    {
        if (IspSetting::get('onesignal_notify_payment', '1') !== '1') {
            return ['success' => false, 'message' => 'Otomasi notifikasi pembayaran OneSignal dinonaktifkan.'];
        }

        $invoice = $payment->invoice;
        $customer = $invoice?->customer;
        if (! $customer) {
            return ['success' => false, 'message' => 'Customer tidak ditemukan.'];
        }

        $formattedAmount = 'Rp '.number_format((float) ($payment->amount_paid ?? $payment->amount ?? 0), 0, ',', '.');
        $title = '✅ Pembayaran Berhasil Dikonfirmasi';
        $message = "Terima kasih {$customer->name}! Pembayaran tagihan internet sebesar {$formattedAmount} telah kami terima dan layanan aktif normal.";

        return $this->sendToCustomer($customer, $title, $message, "/invoices/{$invoice->id}", null, [
            'type' => 'payment',
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
        ]);
    }

    /**
     * Trigger push notification when support ticket is updated.
     */
    public function notifyTicketUpdated(SupportTicket $ticket, string $statusOrMessage): array
    {
        if (IspSetting::get('onesignal_notify_ticket', '1') !== '1') {
            return ['success' => false, 'message' => 'Otomasi notifikasi tiket OneSignal dinonaktifkan.'];
        }

        $customer = $ticket->customer;
        if (! $customer) {
            return ['success' => false, 'message' => 'Customer tidak ditemukan.'];
        }

        $title = '🎫 Update Tiket Gangguan #'.$ticket->ticket_number;
        $message = "Status tiket gangguan Anda [{$ticket->subject}] telah diperbarui: {$statusOrMessage}";

        return $this->sendToCustomer($customer, $title, $message, "/tickets/{$ticket->id}", null, [
            'type' => 'ticket',
            'ticket_id' => $ticket->id,
        ]);
    }

    /**
     * Send push notification to a specific staff user (admin or technician).
     */
    public function sendToUser(User $user, string $title, string $message, ?string $url = null, array $data = []): array
    {
        $externalId = "user_{$user->id}";
        $data['user_id'] = $user->id;
        $data['role'] = $user->role;

        $params = [
            'title' => $title,
            'message' => $message,
            'target_type' => 'user',
            'target_label' => "{$user->name} ({$user->role})",
            'external_ids' => [$externalId],
            'action_url' => $url,
            'data' => $data,
        ];

        if (! empty($user->onesignal_id)) {
            $params['subscription_ids'] = [$user->onesignal_id];
        }

        return $this->sendPushNotification($params);
    }

    /**
     * Send push notification to all active users matching a staff role ('admin' or 'technician').
     */
    public function sendToRole(string $role, string $title, string $message, ?string $url = null, array $data = []): array
    {
        $users = User::query()
            ->where('role', $role)
            ->where('is_active', true)
            ->get(['id', 'onesignal_id']);

        if ($users->isEmpty()) {
            return ['success' => false, 'message' => "Tidak ada user aktif dengan role {$role}."];
        }

        $externalIds = $users->map(fn (User $u): string => "user_{$u->id}")->values()->all();
        $subscriptionIds = $users->pluck('onesignal_id')
            ->map(fn ($id): string => trim((string) $id))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $data['target_role'] = $role;

        return $this->sendPushNotification([
            'title' => $title,
            'message' => $message,
            'target_type' => 'staff_role',
            'role' => $role,
            'target_label' => 'Staf '.ucfirst($role),
            'external_ids' => $externalIds,
            'subscription_ids' => $subscriptionIds,
            'action_url' => $url,
            'data' => $data,
        ]);
    }

    public function sendToAdmins(string $title, string $message, ?string $url = null, array $data = []): array
    {
        return $this->sendToRole('admin', $title, $message, $url, $data);
    }

    public function sendToTechnicians(string $title, string $message, ?string $url = null, array $data = []): array
    {
        return $this->sendToRole('technician', $title, $message, $url, $data);
    }

    public function notifyAdmins(string $title, string $message, array $data = []): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        try {
            $this->sendToAdmins($title, $message, null, $data);
        } catch (\Throwable $e) {
            Log::warning('notifyAdmins OneSignal error: '.$e->getMessage());
        }
    }

    public function notifyTechnicians(string $title, string $message, array $data = []): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        try {
            $this->sendToTechnicians($title, $message, null, $data);
        } catch (\Throwable $e) {
            Log::warning('notifyTechnicians OneSignal error: '.$e->getMessage());
        }
    }

    public function notifyWorkOrderAssigned(WorkOrder $workOrder): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        try {
            $workOrder->loadMissing(['customer:id,name,address', 'technician']);
            $customerName = $workOrder->customer?->name ?? 'Pelanggan';
            $payload = [
                'type' => 'work_order',
                'work_order_id' => $workOrder->id,
                'order_number' => $workOrder->order_number,
            ];

            if ($workOrder->technician && $workOrder->technician->is_active) {
                $this->sendToUser(
                    $workOrder->technician,
                    "🛠️ SPK Baru: {$workOrder->order_number}",
                    "{$workOrder->title} • Pelanggan: {$customerName}",
                    null,
                    $payload
                );

                return;
            }

            $this->sendToTechnicians(
                "🛠️ SPK Terbuka: {$workOrder->order_number}",
                "{$workOrder->title} • Pelanggan: {$customerName}",
                null,
                $payload
            );
        } catch (\Throwable $e) {
            Log::warning('notifyWorkOrderAssigned OneSignal error: '.$e->getMessage());
        }
    }
}
