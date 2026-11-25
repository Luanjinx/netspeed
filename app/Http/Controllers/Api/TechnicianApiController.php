<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\InternetPackage;
use App\Models\IspSetting;
use App\Models\Odp;
use App\Models\PackageRequest;
use App\Models\Router;
use App\Models\SupportTicket;
use App\Models\TicketReply;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\Genieacs\GenieacsApiService;
use App\Services\Notification\WhatsAppService;
use App\Services\Router\MikrotikApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TechnicianApiController extends Controller
{
    public function __construct(
        protected MikrotikApiService $mikrotikService,
        protected GenieacsApiService $genieacsService,
        protected WhatsAppService $whatsAppService
    ) {}

    /**
     * Get technician field dashboard summary and active work orders.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $technician = $request->user();
        $baseQuery = $this->scopedWorkOrderQuery($technician);

        $activeOrders = (clone $baseQuery)
            ->with(['customer.package', 'customer.odp', 'customer.router', 'ticket'])
            ->whereIn('status', ['pending', 'in_progress'])
            ->orderBy('scheduled_date', 'asc')
            ->get();

        $completedToday = (clone $baseQuery)
            ->where('status', 'completed')
            ->whereDate('completed_at', today())
            ->count();

        $totalCompleted = (clone $baseQuery)
            ->where('status', 'completed')
            ->count();

        $psbCount = (clone $baseQuery)
            ->where('type', 'installation')
            ->whereIn('status', ['pending', 'in_progress'])
            ->count();

        $repairCount = (clone $baseQuery)
            ->where('type', 'repair')
            ->whereIn('status', ['pending', 'in_progress'])
            ->count();

        $poolTicketCount = SupportTicket::whereNull('technician_id')
            ->where('status', 'open')
            ->count();

        $pendingPackageRequests = PackageRequest::where('status', PackageRequest::STATUS_PENDING)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'technician' => [
                    'id' => $technician->id,
                    'name' => $technician->name,
                    'email' => $technician->email,
                    'phone' => $technician->phone,
                    'role' => $technician->role,
                ],
                'metrics' => [
                    'active_count' => $activeOrders->count(),
                    'psb_count' => $psbCount,
                    'repair_count' => $repairCount,
                    'completed_today' => $completedToday,
                    'total_completed' => $totalCompleted,
                    'pool_ticket_count' => $poolTicketCount,
                    'pending_package_requests' => $pendingPackageRequests,
                ],
                'active_orders' => $activeOrders->map(fn (WorkOrder $order) => $this->formatWorkOrderSummary($order))->values(),
            ],
        ]);
    }

    /**
     * Get detailed Work Order information including customer ONT diagnostics.
     */
    public function showWorkOrder(Request $request, int $id): JsonResponse
    {
        $technician = $request->user();
        $workOrder = WorkOrder::with([
            'customer.package',
            'customer.odp',
            'customer.router',
            'customer.genieacsServer',
            'ticket.replies.user',
            'technician:id,name,phone,role',
        ])->findOrFail($id);

        if ($this->cannotAccessWorkOrder($technician, $workOrder)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk tugas ini.',
            ], 403);
        }

        $ontData = null;
        $customer = $workOrder->customer;
        if ($customer && $customer->genieacsServer && $customer->genieacs_device_id) {
            try {
                $ontData = $this->genieacsService->getDeviceDiagnosticDetails(
                    $customer->genieacsServer,
                    $customer->genieacs_device_id
                );
            } catch (\Throwable $e) {
                $ontData = ['success' => false, 'message' => $e->getMessage()];
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'work_order' => $this->formatWorkOrderDetail($workOrder),
                'ont_data' => $ontData,
            ],
        ]);
    }

    /**
     * Mark a Work Order as in_progress.
     */
    public function startWorkOrder(Request $request, int $id): JsonResponse
    {
        $technician = $request->user();
        $workOrder = WorkOrder::with('ticket')->findOrFail($id);

        if ($this->cannotAccessWorkOrder($technician, $workOrder)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk tugas ini.',
            ], 403);
        }

        $workOrder->update(['status' => 'in_progress']);

        if ($workOrder->ticket && $workOrder->ticket->status === 'open') {
            $workOrder->ticket->update(['status' => 'in_progress']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Status tugas berhasil diubah menjadi Sedang Dikerjakan.',
            'data' => ['status' => $workOrder->status],
        ]);
    }

    /**
     * Complete a Work Order with optional OPM dBm and completion notes.
     */
    public function completeWorkOrder(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'optical_power_dbm' => 'nullable|string|max:20',
            'completion_notes' => 'nullable|string|max:1000',
        ]);

        $technician = $request->user();
        $workOrder = WorkOrder::with(['customer.package', 'customer.router', 'ticket'])->findOrFail($id);

        if ($this->cannotAccessWorkOrder($technician, $workOrder)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk tugas ini.',
            ], 403);
        }

        $opticalPower = $validated['optical_power_dbm'] ?? null;
        $completionNotes = $validated['completion_notes'] ?? null;

        $workOrder->update([
            'status' => 'completed',
            'optical_power_dbm' => $opticalPower,
            'completion_notes' => $completionNotes,
            'completed_at' => now(),
        ]);

        $customer = $workOrder->customer;

        if ($workOrder->type === 'installation' && $customer) {
            $customer->update([
                'status' => 'active',
                'install_date' => $customer->install_date ?: today(),
                'optical_power_dbm' => $opticalPower ?: $customer->optical_power_dbm,
            ]);

            PackageRequest::where('customer_id', $customer->id)
                ->where('type', PackageRequest::TYPE_REGISTRATION)
                ->where('status', PackageRequest::STATUS_PENDING)
                ->update([
                    'status' => PackageRequest::STATUS_APPROVED,
                    'reviewed_by' => $technician->id,
                    'reviewed_at' => now(),
                    'admin_notes' => $completionNotes ?: 'Disetujui melalui penyelesaian SPK instalasi '.$workOrder->order_number,
                ]);

            if ($customer->connection_type === 'pppoe' && $customer->router) {
                try {
                    $this->mikrotikService->syncCustomerToMikrotik($customer);
                } catch (\Throwable) {
                    //
                }
            }

            if (! empty($customer->phone)) {
                $companyName = IspSetting::get('company_name', 'NetSpeed ISP');
                $waMsg = "Halo {$customer->name},\n\nPemasangan layanan internet {$companyName} di tempat Anda telah *SELESAI* dikerjakan oleh teknisi kami ({$technician->name}).\n\nPaket: {$customer->package?->name}\nStatus: Aktif\n\nSelamat menikmati koneksi internet Anda! Terima kasih.";
                try {
                    $this->whatsAppService->sendMessage($customer->phone, $waMsg);
                } catch (\Throwable) {
                    //
                }
            }
        }

        if ($workOrder->ticket) {
            $workOrder->ticket->update([
                'status' => 'resolved',
                'resolved_at' => now(),
                'optical_power_dbm' => $opticalPower ?: $workOrder->ticket->optical_power_dbm,
                'resolution_notes' => $completionNotes,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => "Tugas {$workOrder->order_number} berhasil diselesaikan!",
            'data' => [
                'id' => $workOrder->id,
                'order_number' => $workOrder->order_number,
                'status' => $workOrder->status,
            ],
        ]);
    }

    /**
     * Check live PPPoE connection status for a customer.
     */
    public function checkLiveStatus(int $customerId): JsonResponse
    {
        $customer = Customer::with(['router', 'package'])->findOrFail($customerId);

        $online = false;
        $ip = $customer->ip_address ?? '-';
        $uptime = '-';
        $message = 'Belum terdeteksi koneksi aktif';

        if ($customer->router && ! empty($customer->pppoe_username)) {
            try {
                $traffic = $this->mikrotikService->getCustomerLiveTraffic($customer);
                if ($traffic['online'] ?? false) {
                    $online = true;
                    $ip = $traffic['ip_address'] ?? $ip;
                    $uptime = $traffic['uptime'] ?? '-';
                    $message = 'User PPPoE online dan terhubung ke MikroTik';
                } else {
                    $message = 'User PPPoE belum dial-in / offline';
                }
            } catch (\Throwable $e) {
                $message = 'Gagal menghubungi router: '.$e->getMessage();
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'online' => $online,
                'ip' => $ip,
                'uptime' => $uptime,
                'message' => $message,
            ],
        ]);
    }

    /**
     * Update customer ONT Wi-Fi SSID & password via GenieACS TR-069.
     */
    public function updateOntWifi(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'ssid' => 'required|string|max:32',
            'password' => 'nullable|string|min:8|max:63',
            'reboot' => 'nullable|boolean',
        ]);

        $customer = Customer::with('genieacsServer')->findOrFail($validated['customer_id']);
        $server = $customer->genieacsServer;

        if (! $server || empty($customer->genieacs_device_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Perangkat ONT belum terhubung ke server GenieACS TR-069.',
            ], 422);
        }

        try {
            $ok = $this->genieacsService->setWifiSettings(
                $server,
                $customer->genieacs_device_id,
                $validated['ssid'],
                $validated['password'] ?? null
            );

            if (! $ok) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengirim konfigurasi Wi-Fi. ONT sedang offline atau tidak merespons.',
                ], 422);
            }

            if ($request->boolean('reboot')) {
                $this->genieacsService->rebootDevice($server, $customer->genieacs_device_id);
            }

            return response()->json([
                'success' => true,
                'message' => "Pengaturan Wi-Fi ONT ({$validated['ssid']}) berhasil dikirim via TR-069!",
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error GenieACS: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reboot customer ONT modem via GenieACS TR-069.
     */
    public function rebootOnt(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
        ]);

        $customer = Customer::with('genieacsServer')->findOrFail($validated['customer_id']);
        $server = $customer->genieacsServer;

        if (! $server || empty($customer->genieacs_device_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Perangkat ONT belum terhubung ke GenieACS.',
            ], 422);
        }

        try {
            if ($this->genieacsService->rebootDevice($server, $customer->genieacs_device_id)) {
                return response()->json([
                    'success' => true,
                    'message' => "Perintah Reboot telah dikirim ke modem ONT {$customer->name}.",
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim perintah reboot. Modem ONT offline.',
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error GenieACS: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * List unassigned open trouble tickets available to claim.
     */
    public function poolTickets(): JsonResponse
    {
        $tickets = SupportTicket::with(['customer.odp', 'customer.package'])
            ->whereNull('technician_id')
            ->where('status', 'open')
            ->latest()
            ->get()
            ->map(function (SupportTicket $ticket) {
                return [
                    'id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                    'subject' => $ticket->subject,
                    'description' => $ticket->description,
                    'priority' => $ticket->priority,
                    'status' => $ticket->status,
                    'created_at' => $ticket->created_at?->toIso8601String(),
                    'customer' => $ticket->customer ? [
                        'id' => $ticket->customer->id,
                        'customer_number' => $ticket->customer->customer_number,
                        'name' => $ticket->customer->name,
                        'phone' => $ticket->customer->phone,
                        'address' => $ticket->customer->address,
                        'odp_name' => $ticket->customer->odp?->name,
                        'package_name' => $ticket->customer->package?->name,
                    ] : null,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $tickets,
        ]);
    }

    /**
     * Claim an open ticket from the pool and create a repair Work Order.
     */
    public function claimTicket(Request $request, int $id): JsonResponse
    {
        $technician = $request->user();
        $ticket = SupportTicket::with('customer')->findOrFail($id);

        if ($ticket->technician_id && $ticket->technician_id !== $technician->id) {
            return response()->json([
                'success' => false,
                'message' => 'Tiket ini telah diambil oleh teknisi lain.',
            ], 422);
        }

        $ticket->update([
            'technician_id' => $technician->id,
            'status' => 'in_progress',
        ]);

        $workOrder = WorkOrder::create([
            'type' => 'repair',
            'technician_id' => $technician->id,
            'customer_id' => $ticket->customer_id,
            'support_ticket_id' => $ticket->id,
            'title' => 'Perbaikan: '.$ticket->subject,
            'description' => $ticket->description,
            'status' => 'in_progress',
            'scheduled_date' => today(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Tiket {$ticket->ticket_number} berhasil diklaim dan masuk ke daftar tugas aktif Anda!",
            'data' => [
                'ticket_id' => $ticket->id,
                'work_order_id' => $workOrder->id,
                'order_number' => $workOrder->order_number,
            ],
        ]);
    }

    /**
     * Reply to a customer support ticket as a technician.
     */
    public function replyTicket(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:5000',
            'is_internal' => 'nullable|boolean',
        ]);

        $technician = $request->user();
        $ticket = SupportTicket::findOrFail($id);

        if ($technician->role === 'technician' && $ticket->technician_id !== null && $ticket->technician_id !== $technician->id) {
            return response()->json([
                'success' => false,
                'message' => 'Tiket ini sedang ditangani oleh teknisi lain.',
            ], 403);
        }

        $reply = TicketReply::create([
            'support_ticket_id' => $ticket->id,
            'user_id' => $technician->id,
            'sender_type' => 'technician',
            'message' => $validated['message'],
            'is_internal' => (bool) ($validated['is_internal'] ?? false),
        ]);

        if ($ticket->status === 'open') {
            $ticket->update(['status' => 'in_progress', 'technician_id' => $technician->id]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Balasan Anda berhasil dikirim ke tiket!',
            'data' => [
                'id' => $reply->id,
                'sender_type' => $reply->sender_type,
                'message' => $reply->message,
                'is_internal' => $reply->is_internal,
                'created_at' => $reply->created_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Get completed Work Orders history for this technician.
     */
    public function history(Request $request): JsonResponse
    {
        $technician = $request->user();

        $query = $this->scopedWorkOrderQuery($technician)
            ->with(['customer.package', 'customer.odp', 'ticket'])
            ->where('status', 'completed');

        if ($request->filled('type')) {
            $query->where('type', $request->query('type'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->query('search'));
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%")
                            ->orWhere('customer_number', 'like', "%{$search}%");
                    });
            });
        }

        $orders = $query->latest('completed_at')->limit(50)->get();

        return response()->json([
            'success' => true,
            'data' => $orders->map(fn (WorkOrder $order) => $this->formatWorkOrderSummary($order))->values(),
        ]);
    }

    /**
     * Get network monitoring metrics (active router resources, ODP capacity, customer status counts).
     */
    public function monitoring(): JsonResponse
    {
        $router = Router::where('is_active', true)->first();
        $routerStats = null;

        if ($router) {
            try {
                $routerStats = $this->mikrotikService->getSystemResources($router);
            } catch (\Throwable $e) {
                $routerStats = ['success' => false, 'message' => $e->getMessage()];
            }
        }

        $odps = Odp::withCount('customers')->get();
        $totalOdpCapacity = (int) $odps->sum('total_ports');
        $totalOdpUsed = (int) $odps->sum('customers_count');
        $odpUtilization = $totalOdpCapacity > 0 ? round(($totalOdpUsed / $totalOdpCapacity) * 100, 1) : 0.0;

        return response()->json([
            'success' => true,
            'data' => [
                'router' => $router ? [
                    'id' => $router->id,
                    'name' => $router->name,
                    'host' => $router->host,
                    'status' => $router->status,
                ] : null,
                'router_stats' => $routerStats,
                'odp_summary' => [
                    'total_odps' => $odps->count(),
                    'total_capacity' => $totalOdpCapacity,
                    'total_used' => $totalOdpUsed,
                    'utilization_percent' => $odpUtilization,
                ],
                'odps' => $odps->map(fn (Odp $odp) => [
                    'id' => $odp->id,
                    'name' => $odp->name,
                    'area' => $odp->area ?? $odp->location ?? null,
                    'total_ports' => (int) ($odp->total_ports ?: 8),
                    'used_ports' => (int) $odp->customers_count,
                    'status' => $odp->status,
                ])->values(),
                'customer_stats' => [
                    'active' => Customer::where('status', 'active')->count(),
                    'isolated' => Customer::where('status', 'isolated')->count(),
                    'pending' => Customer::where('status', 'pending')->count(),
                ],
            ],
        ]);
    }

    /**
     * Get dropdown options (routers, ODPs, packages) for the PSB installation form.
     */
    public function psbOptions(): JsonResponse
    {
        $routers = Router::where('is_active', true)->get(['id', 'name', 'host', 'status']);
        $odps = Odp::where('status', 'active')->withCount('customers')->get();
        $packages = InternetPackage::where('is_active', true)->orderBy('price', 'asc')->get([
            'id',
            'name',
            'type',
            'price',
            'speed_rate',
            'mikrotik_profile',
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'routers' => $routers,
                'odps' => $odps->map(fn (Odp $odp) => [
                    'id' => $odp->id,
                    'name' => $odp->name,
                    'total_ports' => (int) ($odp->total_ports ?: 8),
                    'used_ports' => (int) $odp->customers_count,
                ])->values(),
                'packages' => $packages,
            ],
        ]);
    }

    /**
     * Get port availability for a specific ODP.
     */
    public function getOdpPorts(int $odpId): JsonResponse
    {
        $odp = Odp::findOrFail($odpId);
        $totalPorts = (int) ($odp->total_ports ?: 8);

        $usedPorts = Customer::where('odp_id', $odpId)
            ->whereNotNull('odp_port')
            ->pluck('odp_port')
            ->map(fn ($port) => (int) $port)
            ->toArray();

        $ports = [];
        for ($i = 1; $i <= $totalPorts; $i++) {
            $ports[] = [
                'port' => $i,
                'is_used' => in_array($i, $usedPorts, true),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'odp_id' => $odp->id,
                'odp_name' => $odp->name,
                'total_ports' => $totalPorts,
                'used_count' => count($usedPorts),
                'available_count' => $totalPorts - count($usedPorts),
                'ports' => $ports,
            ],
        ]);
    }

    /**
     * Create a new PSB customer installation from the field app and sync to MikroTik.
     */
    public function storePsbCustomer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:30',
            'email' => 'nullable|email|max:255',
            'address' => 'required|string|max:1000',
            'router_id' => 'required|exists:routers,id',
            'package_id' => 'required|exists:internet_packages,id',
            'odp_id' => 'nullable|exists:odps,id',
            'odp_port' => 'nullable|integer|min:1',
            'pppoe_username' => 'required|string|max:120|unique:customers,pppoe_username',
            'pppoe_password' => 'required|string|min:4|max:120',
            'optical_power_dbm' => 'nullable|string|max:20',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ], [
            'pppoe_username.unique' => 'Username PPPoE ini sudah digunakan oleh pelanggan lain.',
            'name.required' => 'Nama lengkap pelanggan wajib diisi.',
            'phone.required' => 'Nomor WhatsApp wajib diisi.',
        ]);

        $technician = $request->user();
        $customerNumber = Customer::generateCustomerNumber();
        $opticalPower = $validated['optical_power_dbm'] ?? null;

        $customer = Customer::create([
            'customer_number' => $customerNumber,
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'password' => Hash::make($validated['pppoe_password']),
            'address' => $validated['address'],
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'router_id' => $validated['router_id'],
            'package_id' => $validated['package_id'],
            'odp_id' => $validated['odp_id'] ?? null,
            'odp_port' => $validated['odp_port'] ?? null,
            'technician_id' => $technician->id,
            'connection_type' => 'pppoe',
            'pppoe_username' => $validated['pppoe_username'],
            'pppoe_password' => $validated['pppoe_password'],
            'status' => 'active',
            'install_date' => today(),
            'optical_power_dbm' => $opticalPower,
            'due_date_day' => 10,
        ]);

        $workOrder = WorkOrder::create([
            'type' => 'installation',
            'technician_id' => $technician->id,
            'customer_id' => $customer->id,
            'title' => "Pasang Baru Mandiri: {$customer->name}",
            'description' => "Instalasi langsung dari aplikasi teknisi {$technician->name}. Redaman: ".($opticalPower ?: '-').' dBm.',
            'status' => 'completed',
            'scheduled_date' => today(),
            'optical_power_dbm' => $opticalPower,
            'completion_notes' => 'Registrasi dan instalasi sukses langsung dari aplikasi teknisi lapangan.',
            'completed_at' => now(),
        ]);

        if ($customer->router) {
            try {
                $this->mikrotikService->syncCustomerToMikrotik($customer);
            } catch (\Throwable) {
                //
            }
        }

        $companyName = IspSetting::get('company_name', 'NetSpeed ISP');
        $msg = "Halo {$customer->name},\n\nSelamat datang di layanan internet {$companyName}!\n\nPemasangan internet Anda telah berhasil diaktifkan oleh teknisi {$technician->name}.\n\nNo. Pelanggan: {$customer->customer_number}\nPaket: {$customer->package?->name}\nUsername PPPoE: {$customer->pppoe_username}\n\nTerima kasih telah mempercayakan koneksi internet Anda kepada kami.";
        try {
            $this->whatsAppService->sendMessage($customer->phone, $msg);
        } catch (\Throwable) {
            //
        }

        return response()->json([
            'success' => true,
            'message' => "Pelanggan {$customer->name} ({$customerNumber}) berhasil didaftarkan dan diaktifkan!",
            'data' => [
                'customer_id' => $customer->id,
                'customer_number' => $customerNumber,
                'work_order_id' => $workOrder->id,
                'order_number' => $workOrder->order_number,
            ],
        ], 201);
    }

    /**
     * Update authenticated technician password.
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
            'password.min' => 'Password minimal 6 karakter.',
        ]);

        $user = $request->user();
        if (! Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password lama tidak sesuai.',
            ], 422);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password Anda berhasil diperbarui!',
        ]);
    }

    protected function scopedWorkOrderQuery(User $user)
    {
        $query = WorkOrder::query();

        if ($user->role === 'technician') {
            $query->where('technician_id', $user->id);
        }

        return $query;
    }

    protected function cannotAccessWorkOrder(User $user, WorkOrder $workOrder): bool
    {
        return $user->role === 'technician' && $workOrder->technician_id !== $user->id;
    }

    /**
     * @return array<string, mixed>
     */
    protected function formatWorkOrderSummary(WorkOrder $order): array
    {
        $customer = $order->customer;

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'type' => $order->type,
            'title' => $order->title,
            'description' => $order->description,
            'status' => $order->status,
            'scheduled_date' => $order->scheduled_date?->toDateString(),
            'optical_power_dbm' => $order->optical_power_dbm,
            'completion_notes' => $order->completion_notes,
            'completed_at' => $order->completed_at?->toIso8601String(),
            'customer' => $customer ? [
                'id' => $customer->id,
                'customer_number' => $customer->customer_number,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'address' => $customer->address,
                'latitude' => $customer->latitude,
                'longitude' => $customer->longitude,
                'status' => $customer->status,
                'package_name' => $customer->package?->name,
                'odp_name' => $customer->odp?->name,
                'odp_port' => $customer->odp_port,
                'pppoe_username' => $customer->pppoe_username,
            ] : null,
            'ticket' => $order->ticket ? [
                'id' => $order->ticket->id,
                'ticket_number' => $order->ticket->ticket_number,
                'priority' => $order->ticket->priority,
                'status' => $order->ticket->status,
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function formatWorkOrderDetail(WorkOrder $order): array
    {
        $data = $this->formatWorkOrderSummary($order);
        $customer = $order->customer;

        if ($customer) {
            $data['customer'] = array_merge($data['customer'] ?? [], [
                'router_name' => $customer->router?->name,
                'has_genieacs' => $customer->genieacsServer !== null && ! empty($customer->genieacs_device_id),
                'genieacs_device_id' => $customer->genieacs_device_id,
                'optical_power_dbm' => $customer->optical_power_dbm,
            ]);
        }

        if ($order->ticket) {
            $data['ticket'] = [
                'id' => $order->ticket->id,
                'ticket_number' => $order->ticket->ticket_number,
                'subject' => $order->ticket->subject,
                'description' => $order->ticket->description,
                'priority' => $order->ticket->priority,
                'status' => $order->ticket->status,
                'replies' => $order->ticket->replies->map(fn (TicketReply $reply) => [
                    'id' => $reply->id,
                    'sender_type' => $reply->sender_type,
                    'sender_name' => $reply->user?->name ?? 'Sistem',
                    'message' => $reply->message,
                    'is_internal' => (bool) $reply->is_internal,
                    'created_at' => $reply->created_at?->toIso8601String(),
                ])->values(),
            ];
        }

        return $data;
    }
}
