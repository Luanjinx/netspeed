<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\InternetPackage;
use App\Models\IspSetting;
use App\Models\Odp;
use App\Models\PackageRequest;
use App\Models\Router;
use App\Models\SupportTicket;
use App\Models\TicketReply;
use App\Models\WorkOrder;
use App\Services\Genieacs\GenieacsApiService;
use App\Services\Notification\WhatsAppService;
use App\Services\PackageRequestApprovalService;
use App\Services\Router\MikrotikApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class TechnicianPortalController extends Controller
{
    /**
     * Technician Field Dashboard.
     */
    public function dashboard(): View
    {
        $technician = Auth::user();

        // Get work orders assigned to this technician
        $query = WorkOrder::with(['customer.package', 'customer.odp', 'customer.router', 'ticket']);

        if ($technician->role === 'technician') {
            $query->where('technician_id', $technician->id);
        }

        $activeOrders = (clone $query)
            ->whereIn('status', ['pending', 'in_progress'])
            ->orderBy('scheduled_date', 'asc')
            ->get();

        $completedToday = (clone $query)
            ->where('status', 'completed')
            ->whereDate('completed_at', today())
            ->count();

        $totalCompleted = (clone $query)
            ->where('status', 'completed')
            ->count();

        $recentCompleted = (clone $query)
            ->where('status', 'completed')
            ->latest('completed_at')
            ->take(5)
            ->get();

        $psbCount = (clone $query)
            ->where('type', 'installation')
            ->whereIn('status', ['pending', 'in_progress'])
            ->count();

        $repairCount = (clone $query)
            ->where('type', 'repair')
            ->whereIn('status', ['pending', 'in_progress'])
            ->count();

        // Unassigned open tickets available in Pool
        $poolTicketCount = SupportTicket::whereNull('technician_id')
            ->where('status', 'open')
            ->count();

        $companyName = IspSetting::get('company_name', 'NetSpeed ISP');

        return view('technician.dashboard', compact(
            'technician',
            'activeOrders',
            'completedToday',
            'totalCompleted',
            'recentCompleted',
            'psbCount',
            'repairCount',
            'poolTicketCount',
            'companyName'
        ));
    }

    /**
     * Show Work Order Detail page with field action tools & GenieACS access.
     */
    public function showWorkOrder(int $id): View|RedirectResponse
    {
        $technician = Auth::user();

        $workOrder = WorkOrder::with([
            'customer.package',
            'customer.odp',
            'customer.router',
            'customer.genieacsServer',
            'ticket.replies.user',
            'technician',
        ])->findOrFail($id);

        if ($technician->role === 'technician' && $workOrder->technician_id !== $technician->id) {
            return redirect()->route('technician.dashboard')->with('error', 'Anda tidak memiliki hak akses untuk tugas ini.');
        }

        $customer = $workOrder->customer;
        $ontData = null;

        // Fetch GenieACS ONT diagnostics if configured
        if ($customer && $customer->genieacsServer && $customer->genieacs_device_id) {
            try {
                $genieacs = app(GenieacsApiService::class);
                $ontData = $genieacs->getDeviceDiagnosticDetails($customer->genieacsServer, $customer->genieacs_device_id);
            } catch (\Throwable $e) {
                $ontData = ['success' => false, 'message' => $e->getMessage()];
            }
        }

        $companyName = IspSetting::get('company_name', 'NetSpeed ISP');

        return view('technician.work-order', compact('workOrder', 'customer', 'ontData', 'companyName', 'technician'));
    }

    /**
     * Mark work order status as in_progress (Mulai Dikerjakan).
     */
    public function startWorkOrder(int $id): RedirectResponse
    {
        $technician = Auth::user();
        $workOrder = WorkOrder::findOrFail($id);

        if ($technician->role === 'technician' && $workOrder->technician_id !== $technician->id) {
            abort(403);
        }

        $workOrder->update(['status' => 'in_progress']);

        if ($workOrder->ticket && $workOrder->ticket->status === 'open') {
            $workOrder->ticket->update(['status' => 'in_progress']);
        }

        return back()->with('success', 'Status tugas berhasil diubah menjadi Sedang Dikerjakan.');
    }

    /**
     * Complete Work Order (Input OPM dBm, activate customer if installation, resolve ticket).
     */
    public function completeWorkOrder(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'optical_power_dbm' => 'nullable|string|max:20',
            'completion_notes' => 'nullable|string|max:1000',
        ]);

        $technician = Auth::user();
        $workOrder = WorkOrder::with(['customer.package', 'customer.router', 'ticket'])->findOrFail($id);

        if ($technician->role === 'technician' && $workOrder->technician_id !== $technician->id) {
            abort(403);
        }

        $opticalPower = $request->input('optical_power_dbm');
        $completionNotes = $request->input('completion_notes');

        // Update Work Order
        $workOrder->update([
            'status' => 'completed',
            'optical_power_dbm' => $opticalPower,
            'completion_notes' => $completionNotes,
            'completed_at' => now(),
        ]);

        $customer = $workOrder->customer;

        // If installation: activate customer, approve linked PackageRequest & sync to router
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
                    app(MikrotikApiService::class)->syncCustomerToMikrotik($customer);
                } catch (\Throwable $e) {
                    //
                }
            }

            // Send WhatsApp installation completion notification
            if (! empty($customer->phone)) {
                $companyName = IspSetting::get('company_name', 'NetSpeed ISP');
                $waMsg = "Halo {$customer->name},\n\nPemasangan layanan internet {$companyName} di tempat Anda telah *SELESAI* dikerjakan oleh teknisi kami ({$technician->name}).\n\nPaket: {$customer->package?->name}\nStatus: Aktif\n\nSelamat menikmati koneksi internet Anda! Terima kasih.";
                try {
                    app(WhatsAppService::class)->sendMessage($customer->phone, $waMsg);
                } catch (\Throwable $e) {
                    //
                }
            }
        }

        // If ticket is attached, mark as resolved
        if ($workOrder->ticket) {
            $workOrder->ticket->update([
                'status' => 'resolved',
                'resolved_at' => now(),
                'optical_power_dbm' => $opticalPower ?: $workOrder->ticket->optical_power_dbm,
                'resolution_notes' => $completionNotes,
            ]);
        }

        return redirect()->route('technician.dashboard')
            ->with('success', "Tugas {$workOrder->order_number} berhasil diselesaikan!");
    }

    /**
     * Pool Tiket (Daftar tiket gangguan yang belum ditugaskan).
     */
    public function poolTickets(): View
    {
        $technician = Auth::user();

        $tickets = SupportTicket::with(['customer.odp', 'customer.package'])
            ->whereNull('technician_id')
            ->where('status', 'open')
            ->latest()
            ->get();

        $companyName = IspSetting::get('company_name', 'NetSpeed ISP');

        return view('technician.pool-tickets', compact('tickets', 'companyName', 'technician'));
    }

    /**
     * Klaim tiket dari Pool Tiket oleh teknisi.
     */
    public function claimTicket(int $id): RedirectResponse
    {
        $technician = Auth::user();
        $ticket = SupportTicket::with('customer')->findOrFail($id);

        if ($ticket->technician_id && $ticket->technician_id !== $technician->id) {
            return back()->with('error', 'Tiket ini telah diambil oleh teknisi lain.');
        }

        $ticket->update([
            'technician_id' => $technician->id,
            'status' => 'in_progress',
        ]);

        // Auto create Work Order
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

        return redirect()->route('technician.work-order', $workOrder->id)
            ->with('success', "Tiket {$ticket->ticket_number} berhasil diklaim dan masuk ke daftar tugas aktif Anda!");
    }

    /**
     * Riwayat Penanganan Tugas Selesai.
     */
    public function history(Request $request): View
    {
        $technician = Auth::user();

        $query = WorkOrder::with(['customer.package', 'ticket'])
            ->where('status', 'completed');

        if ($technician->role === 'technician') {
            $query->where('technician_id', $technician->id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%")
                            ->orWhere('customer_number', 'like', "%{$search}%");
                    });
            });
        }

        $completedOrders = $query->latest('completed_at')->paginate(15);
        $companyName = IspSetting::get('company_name', 'NetSpeed ISP');

        return view('technician.history', compact('completedOrders', 'companyName', 'technician'));
    }

    /**
     * Monitoring Sistem Ringkas (MikroTik Core, Kapasitas ODP, dan Pelanggan).
     */
    public function monitoring(): View
    {
        $technician = Auth::user();
        $companyName = IspSetting::get('company_name', 'NetSpeed ISP');

        // Router Core resources
        $router = Router::where('is_active', true)->first();
        $routerStats = null;
        if ($router) {
            try {
                $routerStats = app(MikrotikApiService::class)->getSystemResources($router);
            } catch (\Throwable $e) {
                $routerStats = ['success' => false, 'message' => $e->getMessage()];
            }
        }

        // ODP Stats
        $odps = Odp::withCount('customers')->get();
        $totalOdps = $odps->count();
        $totalOdpCapacity = $odps->sum('total_ports');
        $totalOdpUsed = $odps->sum('customers_count');
        $odpUtilization = $totalOdpCapacity > 0 ? round(($totalOdpUsed / $totalOdpCapacity) * 100, 1) : 0;

        // Customer Stats
        $activeCustomers = Customer::where('status', 'active')->count();
        $isolatedCustomers = Customer::where('status', 'isolated')->count();
        $pendingCustomers = Customer::where('status', 'pending')->count();

        return view('technician.monitoring', compact(
            'technician',
            'companyName',
            'router',
            'routerStats',
            'odps',
            'totalOdps',
            'totalOdpCapacity',
            'totalOdpUsed',
            'odpUtilization',
            'activeCustomers',
            'isolatedCustomers',
            'pendingCustomers'
        ));
    }

    /**
     * Form Pasang Baru (PSB) dari Lapangan.
     */
    public function createCustomer(): View
    {
        $technician = Auth::user();
        $companyName = IspSetting::get('company_name', 'NetSpeed ISP');

        $routers = Router::where('is_active', true)->get();
        $odps = Odp::where('status', 'active')->get();
        $packages = InternetPackage::where('is_active', true)->get();

        return view('technician.create-customer', compact(
            'technician',
            'companyName',
            'routers',
            'odps',
            'packages'
        ));
    }

    /**
     * AJAX: Get available port numbers for an ODP.
     */
    public function getOdpPorts(int $odpId): JsonResponse
    {
        $odp = Odp::findOrFail($odpId);
        $totalPorts = (int) ($odp->total_ports ?: 8);

        $usedPorts = Customer::where('odp_id', $odpId)
            ->whereNotNull('odp_port')
            ->pluck('odp_port')
            ->map(fn ($p) => (int) $p)
            ->toArray();

        $availablePorts = [];
        for ($i = 1; $i <= $totalPorts; $i++) {
            $isUsed = in_array($i, $usedPorts);
            $availablePorts[] = [
                'port' => $i,
                'is_used' => $isUsed,
            ];
        }

        return response()->json([
            'success' => true,
            'odp_name' => $odp->name,
            'total_ports' => $totalPorts,
            'used_count' => count($usedPorts),
            'available_count' => $totalPorts - count($usedPorts),
            'ports' => $availablePorts,
        ]);
    }

    /**
     * Simpan Pasang Baru Pelanggan dari Lapangan (Auto aktivasi & sync MikroTik).
     */
    public function storeCustomer(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:30',
            'address' => 'required|string',
            'router_id' => 'required|exists:routers,id',
            'package_id' => 'required|exists:internet_packages,id',
            'odp_id' => 'nullable|exists:odps,id',
            'odp_port' => 'nullable|integer',
            'pppoe_username' => 'required|string|unique:customers,pppoe_username',
            'pppoe_password' => 'required|string',
            'optical_power_dbm' => 'nullable|string|max:20',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ], [
            'pppoe_username.unique' => 'Username PPPoE ini sudah digunakan oleh pelanggan lain.',
            'name.required' => 'Nama lengkap pelanggan wajib diisi.',
            'phone.required' => 'Nomor WhatsApp wajib diisi.',
        ]);

        $technician = Auth::user();

        $customerNumber = Customer::generateCustomerNumber();
        $opticalPower = $request->input('optical_power_dbm');

        $customer = Customer::create([
            'customer_number' => $customerNumber,
            'name' => $request->input('name'),
            'phone' => $request->input('phone'),
            'email' => $request->input('email'),
            'password' => bcrypt($request->input('pppoe_password')),
            'address' => $request->input('address'),
            'latitude' => $request->input('latitude'),
            'longitude' => $request->input('longitude'),
            'router_id' => $request->input('router_id'),
            'package_id' => $request->input('package_id'),
            'odp_id' => $request->input('odp_id'),
            'odp_port' => $request->input('odp_port'),
            'technician_id' => $technician->id,
            'connection_type' => 'pppoe',
            'pppoe_username' => $request->input('pppoe_username'),
            'pppoe_password' => $request->input('pppoe_password'),
            'status' => 'active',
            'install_date' => today(),
            'optical_power_dbm' => $opticalPower,
            'due_date_day' => 10,
        ]);

        // Auto create completed WorkOrder record for tracking
        WorkOrder::create([
            'type' => 'installation',
            'technician_id' => $technician->id,
            'customer_id' => $customer->id,
            'title' => "Pasang Baru Mandiri: {$customer->name}",
            'description' => "Instalasi langsung dari lapangan oleh teknisi {$technician->name}. Redaman: {$opticalPower} dBm.",
            'status' => 'completed',
            'scheduled_date' => today(),
            'optical_power_dbm' => $opticalPower,
            'completion_notes' => 'Registrasi dan instalasi sukses langsung dari portal teknisi lapangan.',
            'completed_at' => now(),
        ]);

        // Sync to MikroTik
        if ($customer->router) {
            try {
                app(MikrotikApiService::class)->syncCustomerToMikrotik($customer);
            } catch (\Throwable $e) {
                //
            }
        }

        // WhatsApp notification
        $companyName = IspSetting::get('company_name', 'NetSpeed ISP');
        $msg = "Halo {$customer->name},\n\nSelamat datang di layanan internet {$companyName}!\n\nPemasangan internet Anda telah berhasil diaktifkan oleh teknisi {$technician->name}.\n\nNo. Pelanggan: {$customer->customer_number}\nPaket: {$customer->package?->name}\nUsername PPPoE: {$customer->pppoe_username}\n\nTerima kasih telah mempercayakan koneksi internet Anda kepada kami.";
        try {
            app(WhatsAppService::class)->sendMessage($customer->phone, $msg);
        } catch (\Throwable $e) {
            //
        }

        return redirect()->route('technician.dashboard')
            ->with('success', "Pelanggan {$customer->name} ({$customerNumber}) berhasil didaftarkan dan diaktifkan di MikroTik!");
    }

    /**
     * Ubah SSID & Password Wi-Fi ONT via GenieACS TR-069.
     */
    public function updateOntWifi(Request $request): RedirectResponse
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'ssid' => 'required|string|max:32',
            'password' => 'nullable|string|min:8',
        ]);

        $customer = Customer::with('genieacsServer')->findOrFail($request->input('customer_id'));
        $server = $customer->genieacsServer;

        if (! $server || empty($customer->genieacs_device_id)) {
            return back()->with('error', 'Perangkat ONT belum terhubung ke server GenieACS TR-069.');
        }

        try {
            $genieacs = app(GenieacsApiService::class);
            $success = $genieacs->setWifiSettings(
                $server,
                $customer->genieacs_device_id,
                $request->input('ssid'),
                $request->input('password')
            );

            if ($success) {
                if ($request->boolean('reboot')) {
                    $genieacs->rebootDevice($server, $customer->genieacs_device_id);
                }

                return back()->with('success', "Pengaturan Wi-Fi ONT ({$request->input('ssid')}) berhasil dikirim via TR-069!");
            }

            return back()->with('error', 'Gagal mengirim konfigurasi Wi-Fi. ONT sedang offline atau tidak merespons.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Error GenieACS: '.$e->getMessage());
        }
    }

    /**
     * Reboot Modem ONT via GenieACS TR-069.
     */
    public function rebootOnt(Request $request): RedirectResponse
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
        ]);

        $customer = Customer::with('genieacsServer')->findOrFail($request->input('customer_id'));
        $server = $customer->genieacsServer;

        if (! $server || empty($customer->genieacs_device_id)) {
            return back()->with('error', 'Perangkat ONT belum terhubung ke GenieACS.');
        }

        try {
            $genieacs = app(GenieacsApiService::class);
            if ($genieacs->rebootDevice($server, $customer->genieacs_device_id)) {
                return back()->with('success', "Perintah Reboot telah dikirim ke modem ONT {$customer->name}.");
            }

            return back()->with('error', 'Gagal mengirim perintah reboot. Modem ONT offline.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Error GenieACS: '.$e->getMessage());
        }
    }

    /**
     * AJAX: Live check customer connection status in MikroTik & TR-069.
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
                $service = app(MikrotikApiService::class);
                $traffic = $service->getCustomerLiveTraffic($customer);
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
            'online' => $online,
            'ip' => $ip,
            'uptime' => $uptime,
            'message' => $message,
        ]);
    }

    /**
     * Technician Profile.
     */
    public function profile(): View
    {
        $technician = Auth::user();
        $companyName = IspSetting::get('company_name', 'NetSpeed ISP');

        return view('technician.profile', compact('technician', 'companyName'));
    }

    /**
     * Update Technician Password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
            'password.min' => 'Password minimal 6 karakter.',
        ]);

        $user = Auth::user();

        if (! Hash::check($request->input('current_password'), $user->password)) {
            return back()->withErrors(['current_password' => 'Password lama tidak sesuai.']);
        }

        $user->update([
            'password' => bcrypt($request->input('password')),
        ]);

        return back()->with('success', 'Password Anda berhasil diperbarui!');
    }

    /**
     * Send reply from technician to a support ticket.
     */
    public function replyTicket(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'message' => 'required|string|max:5000',
            'attachment' => 'nullable|image|max:5120',
        ]);

        $technician = Auth::user();
        $ticket = SupportTicket::findOrFail($id);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('tickets', 'public');
        }

        TicketReply::create([
            'support_ticket_id' => $ticket->id,
            'user_id' => $technician->id,
            'sender_type' => 'technician',
            'message' => $request->input('message'),
            'attachment' => $attachmentPath,
            'is_internal' => (bool) $request->boolean('is_internal'),
        ]);

        if ($ticket->status === 'open') {
            $ticket->update(['status' => 'in_progress', 'technician_id' => $technician->id]);
        }

        return back()->with('success', 'Balasan Anda berhasil dikirim ke tiket!');
    }

    /**
     * List pending & processed registration and package upgrade requests for Technician approval.
     */
    public function packageRequests(Request $request): View
    {
        $technician = Auth::user();
        $statusFilter = $request->query('status', 'pending');

        $query = PackageRequest::with([
            'customer',
            'currentPackage',
            'requestedPackage',
            'invoice',
            'workOrder',
            'reviewer',
        ])->latest();

        if (in_array($statusFilter, [PackageRequest::STATUS_PENDING, PackageRequest::STATUS_APPROVED, PackageRequest::STATUS_REJECTED], true)) {
            $query->where('status', $statusFilter);
        }

        $requests = $query->get();
        $pendingCount = PackageRequest::where('status', PackageRequest::STATUS_PENDING)->count();
        $companyName = IspSetting::get('company_name', 'NetSpeed ISP');

        return view('technician.package-requests', compact(
            'technician',
            'requests',
            'statusFilter',
            'pendingCount',
            'companyName'
        ));
    }

    /**
     * Approve a registration or package upgrade request as Technician.
     */
    public function approvePackageRequest(Request $request, int $id, PackageRequestApprovalService $approvalService): RedirectResponse
    {
        $technician = Auth::user();
        $packageRequest = PackageRequest::findOrFail($id);

        try {
            $approvalService->approve($packageRequest, $technician, $request->input('notes'));

            return back()->with('success', "Pengajuan {$packageRequest->request_number} berhasil di-ACC!");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Reject a registration or package upgrade request as Technician.
     */
    public function rejectPackageRequest(Request $request, int $id, PackageRequestApprovalService $approvalService): RedirectResponse
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $technician = Auth::user();
        $packageRequest = PackageRequest::findOrFail($id);

        try {
            $approvalService->reject($packageRequest, $technician, (string) $request->input('reason'));

            return back()->with('success', "Pengajuan {$packageRequest->request_number} telah ditolak.");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
