<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\AgentTransaction;
use App\Models\AgentWithdrawal;
use App\Models\Customer;
use App\Models\GenieacsServer;
use App\Models\InternetPackage;
use App\Models\Invoice;
use App\Models\Odp;
use App\Models\Payment;
use App\Models\Router;
use App\Models\SupportTicket;
use App\Models\TicketReply;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\Agent\AgentService;
use App\Services\Billing\BillingService;
use App\Services\Genieacs\GenieacsApiService;
use App\Services\Notification\OneSignalService;
use App\Services\Router\MikrotikApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class AdminOperationsController extends Controller
{
    public function __construct(
        protected BillingService $billingService,
        protected MikrotikApiService $mikrotikService,
        protected GenieacsApiService $genieacsService,
        protected AgentService $agentService
    ) {}

    public function customerMeta(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'packages' => InternetPackage::where('is_active', true)->orderBy('price')->get(['id', 'name', 'speed_rate', 'price', 'type', 'mikrotik_profile']),
                'routers' => Router::where('is_active', true)->orderBy('name')->get(['id', 'name', 'host', 'status']),
                'odps' => Odp::orderBy('name')->get(['id', 'name', 'code', 'total_ports', 'status']),
                'technicians' => User::whereIn('role', ['technician', 'admin'])->where('is_active', true)->orderBy('name')->get(['id', 'name', 'phone', 'role']),
                'genieacs_servers' => GenieacsServer::where('is_active', true)->orderBy('name')->get(['id', 'name', 'base_url']),
            ],
        ]);
    }

    public function customersIndex(Request $request): JsonResponse
    {
        $query = Customer::with(['package:id,name,speed_rate,price', 'router:id,name', 'odp:id,name,code', 'technician:id,name']);

        if ($request->filled('search')) {
            $search = trim((string) $request->query('search'));
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('customer_number', 'like', "%{$search}%")
                    ->orWhere('pppoe_username', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        return response()->json([
            'success' => true,
            'data' => $query->latest()->paginate(25),
        ]);
    }

    public function customerShow(int $id): JsonResponse
    {
        $customer = Customer::with([
            'package',
            'router',
            'odp',
            'technician:id,name,phone',
            'genieacsServer:id,name,base_url',
            'invoices' => fn ($q) => $q->latest()->take(12),
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $customer,
        ]);
    }

    public function customerStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:30',
            'email' => 'nullable|email|max:255',
            'password' => 'nullable|string|min:6',
            'address' => 'required|string|max:500',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'router_id' => 'nullable|integer|exists:routers,id',
            'odp_id' => 'nullable|integer|exists:odps,id',
            'odp_port' => 'nullable|integer|min:1',
            'package_id' => 'nullable|integer|exists:internet_packages,id',
            'technician_id' => 'nullable|integer|exists:users,id',
            'connection_type' => 'nullable|string|in:pppoe,hotspot,static',
            'pppoe_username' => 'nullable|string|max:100',
            'pppoe_password' => 'nullable|string|max:100',
            'status' => 'nullable|string|in:active,isolated,pending,suspended',
            'notes' => 'nullable|string|max:1000',
        ]);

        $plainPassword = $validated['password'] ?? '123456';
        $customer = Customer::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'password' => Hash::make($plainPassword),
            'address' => $validated['address'],
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'router_id' => $validated['router_id'] ?? null,
            'odp_id' => $validated['odp_id'] ?? null,
            'odp_port' => $validated['odp_port'] ?? null,
            'package_id' => $validated['package_id'] ?? null,
            'technician_id' => $validated['technician_id'] ?? null,
            'connection_type' => $validated['connection_type'] ?? 'pppoe',
            'pppoe_username' => $validated['pppoe_username'] ?? null,
            'pppoe_password' => $validated['pppoe_password'] ?? null,
            'status' => $validated['status'] ?? 'active',
            'registered_at' => now(),
            'notes' => $validated['notes'] ?? null,
        ]);

        $user = User::updateOrCreate(
            ['phone' => $customer->phone],
            [
                'name' => $customer->name,
                'email' => $customer->email ?: "{$customer->customer_number}@customer.netspeed",
                'password' => Hash::make($plainPassword),
                'role' => 'customer',
                'is_active' => true,
            ]
        );
        $customer->update(['user_id' => $user->id]);

        if ($customer->router && $customer->pppoe_username) {
            $this->mikrotikService->syncCustomerToMikrotik($customer->fresh(['router', 'package']));
        }

        return response()->json([
            'success' => true,
            'message' => "Pelanggan {$customer->name} berhasil ditambahkan.",
            'data' => $customer->fresh(['package', 'router', 'odp']),
        ], 201);
    }

    public function customerUpdate(Request $request, int $id): JsonResponse
    {
        $customer = Customer::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:30',
            'email' => 'nullable|email|max:255',
            'password' => 'nullable|string|min:6',
            'address' => 'required|string|max:500',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'router_id' => 'nullable|integer|exists:routers,id',
            'odp_id' => 'nullable|integer|exists:odps,id',
            'odp_port' => 'nullable|integer|min:1',
            'package_id' => 'nullable|integer|exists:internet_packages,id',
            'technician_id' => 'nullable|integer|exists:users,id',
            'connection_type' => 'nullable|string|in:pppoe,hotspot,static',
            'pppoe_username' => 'nullable|string|max:100',
            'pppoe_password' => 'nullable|string|max:100',
            'status' => 'nullable|string|in:active,isolated,pending,suspended',
            'notes' => 'nullable|string|max:1000',
        ]);

        $payload = collect($validated)->except('password')->toArray();
        if (! empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }
        $customer->update($payload);

        if ($customer->user) {
            $userUpdate = [
                'name' => $customer->name,
                'phone' => $customer->phone,
            ];
            if (! empty($validated['password'])) {
                $userUpdate['password'] = Hash::make($validated['password']);
            }
            $customer->user->update($userUpdate);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data pelanggan berhasil diperbarui.',
            'data' => $customer->fresh(['package', 'router', 'odp']),
        ]);
    }

    public function customerDestroy(int $id): JsonResponse
    {
        $customer = Customer::findOrFail($id);
        if ($customer->router && $customer->pppoe_username) {
            $this->mikrotikService->removePppSecret($customer->router, $customer->pppoe_username);
        }
        $customer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data pelanggan berhasil dihapus.',
        ]);
    }

    public function customerToggleStatus(int $id): JsonResponse
    {
        $customer = Customer::with(['router', 'package'])->findOrFail($id);

        if ($customer->status === 'isolated') {
            $ok = $customer->router ? $this->mikrotikService->activateCustomer($customer) : true;
            $customer->update(['status' => 'active']);
            $msg = $ok ? 'Layanan pelanggan diaktifkan kembali.' : 'Status diupdate aktif (router offline).';
        } else {
            $ok = $customer->router ? $this->mikrotikService->isolateCustomer($customer) : true;
            $customer->update(['status' => 'isolated']);
            $msg = $ok ? 'Pelanggan berhasil diisolir.' : 'Status diupdate isolir (router offline).';
        }

        return response()->json([
            'success' => true,
            'message' => $msg,
            'data' => $customer->fresh(),
        ]);
    }

    public function customerLiveStatus(int $id): JsonResponse
    {
        $customer = Customer::with(['router', 'genieacsServer'])->findOrFail($id);
        $session = $this->mikrotikService->getCustomerSessionStatus($customer);

        $ont = null;
        if ($customer->genieacsServer && $customer->genieacs_device_id) {
            try {
                $ont = $this->genieacsService->getDeviceDiagnosticDetails(
                    $customer->genieacsServer,
                    $customer->genieacs_device_id
                );
            } catch (\Throwable) {
                $ont = null;
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'session' => $session,
                'ont' => $ont,
            ],
        ]);
    }

    public function customerOntReboot(int $id): JsonResponse
    {
        $customer = Customer::with('genieacsServer')->findOrFail($id);
        if (! $customer->genieacsServer || ! $customer->genieacs_device_id) {
            return response()->json(['success' => false, 'message' => 'Perangkat GenieACS belum ditautkan.'], 422);
        }

        $ok = $this->genieacsService->rebootDevice($customer->genieacsServer, $customer->genieacs_device_id);

        return response()->json([
            'success' => $ok,
            'message' => $ok ? 'Perintah reboot ONT berhasil dikirim.' : 'Gagal mengirim perintah reboot ONT.',
        ]);
    }

    public function customerOntWifi(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'ssid' => 'required|string|min:2|max:32',
            'password' => 'nullable|string|min:8|max:63',
        ]);

        $customer = Customer::with('genieacsServer')->findOrFail($id);
        if (! $customer->genieacsServer || ! $customer->genieacs_device_id) {
            return response()->json(['success' => false, 'message' => 'Perangkat GenieACS belum ditautkan.'], 422);
        }

        $ok = $this->genieacsService->setWifiSettings(
            $customer->genieacsServer,
            $customer->genieacs_device_id,
            $validated['ssid'],
            $validated['password'] ?? null
        );

        return response()->json([
            'success' => $ok,
            'message' => $ok ? 'Konfigurasi Wi-Fi ONT berhasil diperbarui.' : 'Gagal memperbarui Wi-Fi ONT.',
        ]);
    }

    public function customerGenerateInvoice(Request $request, int $id): JsonResponse
    {
        $customer = Customer::with('package')->findOrFail($id);
        $period = $request->input('period');
        $invoice = $this->billingService->generateInvoice($customer, $period);

        return response()->json([
            'success' => true,
            'message' => "Tagihan {$invoice->invoice_number} berhasil dibuat.",
            'data' => $invoice->load('customer'),
        ], 201);
    }

    public function invoicesIndex(Request $request): JsonResponse
    {
        $query = Invoice::with(['customer:id,name,customer_number,phone,pppoe_username,status', 'package:id,name,price']);

        if ($request->filled('search')) {
            $search = trim((string) $request->query('search'));
            $query->where(function ($outer) use ($search): void {
                $outer->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($q) use ($search): void {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('customer_number', 'like', "%{$search}%")
                            ->orWhere('pppoe_username', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        return response()->json([
            'success' => true,
            'data' => $query->latest()->paginate(25),
        ]);
    }

    public function invoiceGenerateMonthly(Request $request): JsonResponse
    {
        $period = $request->input('period') ?: now()->format('Y-m');
        $count = $this->billingService->generateMonthlyInvoices($period);

        return response()->json([
            'success' => true,
            'message' => "{$count} tagihan periode {$period} berhasil digenerate.",
            'data' => ['generated_count' => $count],
        ]);
    }

    public function invoiceMarkPaid(Request $request, int $id): JsonResponse
    {
        $invoice = Invoice::with('customer')->findOrFail($id);
        if ($invoice->status === 'paid') {
            return response()->json(['success' => false, 'message' => 'Tagihan sudah berstatus lunas.'], 422);
        }

        $method = (string) $request->input('payment_method', 'manual_admin');
        $notes = (string) $request->input('notes', 'Diverifikasi oleh Admin via Mobile App');
        $ok = $this->billingService->markInvoicePaid($invoice, $method, $notes);

        return response()->json([
            'success' => $ok,
            'message' => $ok ? "Tagihan {$invoice->invoice_number} berhasil ditandai LUNAS." : 'Gagal melunasi tagihan.',
            'data' => $invoice->fresh('customer'),
        ]);
    }

    public function paymentsIndex(Request $request): JsonResponse
    {
        $query = Payment::with(['invoice.customer:id,name,customer_number,phone', 'verifier:id,name'])->latest();

        if ($request->filled('verified')) {
            $query->where('is_verified', $request->query('verified') === '1');
        }

        $paginated = $query->paginate(25);
        $paginated->getCollection()->transform(function (Payment $payment): array {
            $arr = $payment->toArray();
            $arr['proof_url'] = $payment->proof_image ? url('storage/'.ltrim($payment->proof_image, '/')) : null;

            return $arr;
        });

        return response()->json([
            'success' => true,
            'data' => $paginated,
        ]);
    }

    public function paymentVerify(Request $request, int $id): JsonResponse
    {
        $payment = Payment::with('invoice')->findOrFail($id);
        $payment->update([
            'is_verified' => true,
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
            'notes' => $request->input('notes', $payment->notes),
        ]);

        if ($payment->invoice && $payment->invoice->status !== 'paid') {
            $this->billingService->markInvoicePaid(
                $payment->invoice,
                $payment->payment_method ?: 'bank_transfer',
                'Verifikasi pembayaran oleh Admin'
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran berhasil diverifikasi.',
            'data' => $payment->fresh('invoice'),
        ]);
    }

    public function workOrdersIndex(Request $request): JsonResponse
    {
        $query = WorkOrder::with([
            'customer:id,name,customer_number,phone,address,package_id',
            'customer.package:id,name',
            'technician:id,name,phone',
        ])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }
        if ($request->filled('type')) {
            $query->where('type', $request->query('type'));
        }

        return response()->json([
            'success' => true,
            'data' => $query->paginate(25),
        ]);
    }

    public function workOrderStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|string|in:installation,repair,dismantle,relocation',
            'customer_id' => 'required|integer|exists:customers,id',
            'technician_id' => 'nullable|integer|exists:users,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'scheduled_date' => 'nullable|date',
        ]);

        $order = WorkOrder::create([
            'type' => $validated['type'],
            'customer_id' => $validated['customer_id'],
            'technician_id' => $validated['technician_id'] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => 'pending',
            'scheduled_date' => $validated['scheduled_date'] ?? now()->addDay(),
        ]);

        app(OneSignalService::class)->notifyWorkOrderAssigned($order);

        return response()->json([
            'success' => true,
            'message' => "SPK {$order->order_number} berhasil dibuat.",
            'data' => $order->load(['customer', 'technician:id,name']),
        ], 201);
    }

    public function workOrderAssign(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'technician_id' => 'nullable|integer|exists:users,id',
            'status' => 'nullable|string|in:pending,in_progress,completed,cancelled',
            'scheduled_date' => 'nullable|date',
        ]);

        $order = WorkOrder::findOrFail($id);
        $previousTechId = $order->technician_id;
        $order->update($validated);

        if ($order->technician_id && (int) $order->technician_id !== (int) $previousTechId) {
            app(OneSignalService::class)->notifyWorkOrderAssigned($order->fresh());
        }

        return response()->json([
            'success' => true,
            'message' => "SPK {$order->order_number} berhasil diperbarui.",
            'data' => $order->fresh(['customer', 'technician:id,name']),
        ]);
    }

    public function ticketsIndex(Request $request): JsonResponse
    {
        $query = SupportTicket::with([
            'customer:id,name,customer_number,phone',
            'technician:id,name',
        ])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        return response()->json([
            'success' => true,
            'data' => $query->paginate(25),
        ]);
    }

    public function ticketShow(int $id): JsonResponse
    {
        $ticket = SupportTicket::with([
            'customer:id,name,customer_number,phone,address,pppoe_username',
            'technician:id,name,phone',
            'replies.user:id,name,role',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $ticket,
        ]);
    }

    public function ticketReply(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:2000',
            'status' => 'nullable|string|in:open,in_progress,resolved,closed',
            'technician_id' => 'nullable|integer|exists:users,id',
        ]);

        $ticket = SupportTicket::findOrFail($id);
        TicketReply::create([
            'support_ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'message' => $validated['message'],
            'is_admin' => true,
        ]);

        $update = [];
        if (! empty($validated['status'])) {
            $update['status'] = $validated['status'];
            if (in_array($validated['status'], ['resolved', 'closed'], true)) {
                $update['resolved_at'] = now();
            }
        }
        if (array_key_exists('technician_id', $validated)) {
            $update['technician_id'] = $validated['technician_id'];
        }
        if ($update !== []) {
            $ticket->update($update);
        }

        return response()->json([
            'success' => true,
            'message' => 'Balasan tiket terkirim.',
            'data' => $ticket->fresh(['customer', 'technician:id,name', 'replies.user:id,name,role']),
        ]);
    }

    public function agentsIndex(Request $request): JsonResponse
    {
        $query = Agent::with('user:id,name,email,role')->latest();

        if ($request->filled('approval_status') && Schema::hasColumn('agents', 'approval_status')) {
            $query->where('approval_status', $request->query('approval_status'));
        }
        if ($request->filled('search')) {
            $search = trim((string) $request->query('search'));
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('agent_code', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return response()->json([
            'success' => true,
            'data' => $query->paginate(25),
        ]);
    }

    public function agentApprove(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'commission_per_transaction' => 'required|numeric|min:0',
            'initial_balance' => 'nullable|numeric|min:0',
        ]);

        $agent = Agent::findOrFail($id);
        $agent->update([
            'approval_status' => Agent::STATUS_APPROVED,
            'is_active' => true,
            'commission_per_transaction' => (float) $validated['commission_per_transaction'],
            'rejection_reason' => null,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        $initialBalance = (float) ($validated['initial_balance'] ?? 0);
        if ($initialBalance > 0) {
            $this->agentService->topUpBalance(
                $agent->fresh(),
                $initialBalance,
                'Deposit awal persetujuan mitra agen (Admin App)',
                $request->user()
            );
        }

        return response()->json([
            'success' => true,
            'message' => "Mitra Agen {$agent->name} disetujui.",
            'data' => $agent->fresh(),
        ]);
    }

    public function agentReject(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $agent = Agent::findOrFail($id);
        $agent->update([
            'approval_status' => Agent::STATUS_REJECTED,
            'is_active' => false,
            'rejection_reason' => $validated['reason'],
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Pengajuan agen {$agent->name} ditolak.",
            'data' => $agent->fresh(),
        ]);
    }

    public function agentTopUp(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1000',
            'notes' => 'nullable|string|max:255',
        ]);

        $agent = Agent::findOrFail($id);
        $tx = $this->agentService->topUpBalance(
            $agent,
            (float) $validated['amount'],
            $validated['notes'] ?? null,
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Saldo agen berhasil ditambah.',
            'data' => [
                'agent' => $agent->fresh(),
                'transaction' => $tx,
            ],
        ]);
    }

    public function agentToggleActive(int $id): JsonResponse
    {
        $agent = Agent::findOrFail($id);
        $agent->update(['is_active' => ! $agent->is_active]);

        return response()->json([
            'success' => true,
            'message' => $agent->is_active ? 'Mitra Agen diaktifkan.' : 'Mitra Agen dinonaktifkan.',
            'data' => $agent->fresh(),
        ]);
    }

    public function agentTransactions(int $id): JsonResponse
    {
        $transactions = AgentTransaction::where('agent_id', $id)
            ->with('invoice.customer:id,name,customer_number')
            ->latest()
            ->take(50)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $transactions,
        ]);
    }

    public function agentPaymentSearch(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $invoices = $this->agentService->searchUnpaidInvoices($q, 15);
        $data = $invoices->map(function (Invoice $inv): array {
            $cust = $inv->customer;

            return [
                'id' => $inv->id,
                'invoice_number' => $inv->invoice_number,
                'period' => $inv->period,
                'total_amount' => (float) $inv->total_amount,
                'formatted_total' => 'Rp '.number_format((float) $inv->total_amount, 0, ',', '.'),
                'due_date' => $inv->due_date ? $inv->due_date->format('d/m/Y') : '-',
                'customer_name' => $cust?->name ?? 'Pelanggan',
                'customer_number' => $cust?->customer_number ?? '-',
                'pppoe_username' => $cust?->pppoe_username ?? '-',
                'package_name' => $cust?->package?->name ?? ($inv->package?->name ?? '-'),
            ];
        })->values();

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function agentPayInvoice(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'agent_id' => 'required|integer|exists:agents,id',
            'invoice_id' => 'required|integer|exists:invoices,id',
        ]);

        $agent = Agent::findOrFail($validated['agent_id']);
        $invoice = Invoice::with(['customer.package', 'customer.router', 'package'])->findOrFail($validated['invoice_id']);

        try {
            $tx = $this->agentService->payInvoice($agent, $invoice, $request->user());

            return response()->json([
                'success' => true,
                'message' => "Tagihan {$invoice->invoice_number} lunas via loket {$agent->name}.",
                'data' => [
                    'transaction' => $tx,
                    'agent' => $agent->fresh(),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function agentWithdrawalsIndex(Request $request): JsonResponse
    {
        if (! Schema::hasTable('agent_withdrawals')) {
            return response()->json([
                'success' => true,
                'data' => [
                    'data' => [],
                    'current_page' => 1,
                    'last_page' => 1,
                    'total' => 0,
                ],
            ]);
        }

        $query = AgentWithdrawal::with(['agent:id,agent_code,name,phone,balance', 'processor:id,name'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        return response()->json([
            'success' => true,
            'data' => $query->paginate(25),
        ]);
    }

    public function agentWithdrawalApprove(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'transfer_reference' => 'nullable|string|max:150',
        ]);

        $withdrawal = AgentWithdrawal::with('agent')->findOrFail($id);

        try {
            $updated = $this->agentService->approveWithdrawal(
                $withdrawal,
                $request->user(),
                $validated['transfer_reference'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => "Penarikan {$updated->withdrawal_code} ({$updated->formatted_amount}) disetujui.",
                'data' => $updated,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function agentWithdrawalReject(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $withdrawal = AgentWithdrawal::with('agent')->findOrFail($id);

        try {
            $updated = $this->agentService->rejectWithdrawal(
                $withdrawal,
                $request->user(),
                $validated['reason']
            );

            return response()->json([
                'success' => true,
                'message' => "Penarikan {$updated->withdrawal_code} ditolak dan saldo {$updated->formatted_amount} dikembalikan ke agen.",
                'data' => $updated,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
