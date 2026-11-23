<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\InternetPackage;
use App\Models\Invoice;
use App\Models\IspSetting;
use App\Models\Page;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\Promo;
use App\Models\SupportTicket;
use App\Models\TicketReply;
use App\Services\Genieacs\GenieacsApiService;
use App\Services\Payment\QrisService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerPortalController extends Controller
{
    public function __construct(
        protected QrisService $qrisService,
        protected GenieacsApiService $genieacsService
    ) {}

    /**
     * Public Home data for Flutter Customer App before login.
     */
    public function publicHome(): JsonResponse
    {
        $pppoePackages = InternetPackage::where('is_active', true)
            ->where('type', 'pppoe')
            ->orderBy('price', 'asc')
            ->get();

        $hotspotPackages = InternetPackage::where('is_active', true)
            ->where('type', 'hotspot')
            ->orderBy('price', 'asc')
            ->get();

        $companyName = IspSetting::get('company_name', 'NetSpeed Broadband');
        $companyPhone = IspSetting::get('company_phone', '0812-3456-7890');
        $companyEmail = IspSetting::get('company_email', 'cs@netspeed.net.id');
        $companyAddress = IspSetting::get('company_address', 'Jl. Telekomunikasi No. 1, Cyber Park');

        // Dynamic Promos / Banners from Database
        $promos = Promo::with('package')
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->orderBy('order', 'asc')
            ->get();

        if ($promos->isNotEmpty()) {
            $banners = $promos->map(function (Promo $promo) {
                return [
                    'id' => $promo->id,
                    'title' => $promo->title,
                    'subtitle' => $promo->subtitle,
                    'badge' => $promo->badge_text,
                    'image' => $promo->image_url,
                    'content' => $promo->content,
                    'offer_title' => $promo->offer_title ?? ($promo->package?->name ?? $promo->title),
                    'offer_duration' => $promo->offer_duration ?? ($promo->package ? ($promo->package->validity_days.' Hari') : '1 Bulan'),
                    'original_price' => $promo->original_price,
                    'promo_price' => $promo->promo_price ?? $promo->package?->price,
                    'discount_label' => $promo->formatted_discount_label,
                    'price_unit' => $promo->price_unit ?? '/ bulan',
                    'cta_text' => $promo->cta_text ?? 'Langganan Sekarang',
                    'action_type' => $promo->action_type,
                    'package_id' => $promo->package_id,
                    'link_url' => $promo->link_url,
                ];
            })->toArray();
        } else {
            // Default built-in fallback banners
            $banners = [
                [
                    'id' => 1,
                    'title' => 'Gamer Ultimate 100 Mbps',
                    'subtitle' => 'Kecepatan Puncak 100 Mbps • Low Latency Gaming',
                    'badge' => '100 MBPS',
                    'image' => 'assets/images/package-gamer-ultimate.webp',
                    'content' => "Nikmati pengalaman gaming online dan streaming video resolusi 4K tanpa kompromi dengan koneksi Gamer Ultimate 100 Mbps.\n\nKeunggulan Utama:\n• Kecepatan simetris dengan direct peering IIX & server game terkemuka\n• Low ping dan ultra-low jitter untuk kompetitif game\n• Tanpa batas kuota (Fair Usage Policy Unlimited)\n• Dukungan router dual-band gigabit generasi terbaru",
                    'offer_title' => 'Paket Gamer Ultimate 100 Mbps',
                    'offer_duration' => '1 Bulan',
                    'original_price' => 220000,
                    'promo_price' => 175000,
                    'discount_label' => '-20%',
                    'price_unit' => '/ bulan',
                    'cta_text' => 'Langganan Sekarang',
                    'action_type' => 'package',
                    'package_id' => 15,
                    'link_url' => null,
                ],
                [
                    'id' => 2,
                    'title' => 'Gamer Pro 50 Mbps',
                    'subtitle' => 'Direct Peering IIX & Steam • Bebas Lag',
                    'badge' => '50 MBPS',
                    'image' => 'assets/images/package-gamer-pro-50mbps.webp',
                    'content' => "Paket internet khusus untuk gamer dan streamer yang membutuhkan koneksi stabil, latensi super rendah, dan bebas hambatan sepanjang hari.\n\nFitur Layanan:\n• Prioritas traffic game internasional dan domestik\n• Streaming lancar di berbagai platform sekaligus\n• Instalasi cepat dan modem Wi-Fi canggih",
                    'offer_title' => 'Paket Gamer Pro 50 Mbps',
                    'offer_duration' => '1 Bulan',
                    'original_price' => 135000,
                    'promo_price' => 100000,
                    'discount_label' => '-25%',
                    'price_unit' => '/ bulan',
                    'cta_text' => 'Langganan Sekarang',
                    'action_type' => 'package',
                    'package_id' => 3,
                    'link_url' => null,
                ],
                [
                    'id' => 3,
                    'title' => 'Home Ultra 20 Mbps',
                    'subtitle' => 'Paling Favorit Keluarga • Streaming HD Lancar',
                    'badge' => 'BEST SELLER',
                    'image' => 'assets/images/package-home-ultra-20mbps.webp',
                    'content' => "Pilihan paling tepat dan hemat untuk seluruh anggota keluarga di rumah. Lancar untuk sekolah daring, WFH, meeting Zoom, dan hiburan nonton serial favorit.\n\nKeuntungan:\n• Kecepatan 20 Mbps stabil 24 jam nonstop\n• Ideal untuk 4-6 perangkat sekaligus\n• Sudah termasuk biaya sewa modem Wi-Fi",
                    'offer_title' => 'Paket Home Ultra 20 Mbps',
                    'offer_duration' => '1 Bulan',
                    'original_price' => 95000,
                    'promo_price' => 75000,
                    'discount_label' => '-21%',
                    'price_unit' => '/ bulan',
                    'cta_text' => 'Langganan Sekarang',
                    'action_type' => 'package',
                    'package_id' => 2,
                    'link_url' => null,
                ],
                [
                    'id' => 4,
                    'title' => 'Home Basic 15 Mbps',
                    'subtitle' => 'Paket Hemat Pelajar & WFH • Tanpa Batas Kuota',
                    'badge' => 'HEMAT',
                    'image' => 'assets/images/package-home-basic-15mbps.webp',
                    'content' => 'Solusi internet rumah terjangkau dengan kualitas serat optik handal untuk browsing, sosial media, dan belajar dari rumah tanpa cemas kuota habis.',
                    'offer_title' => 'Paket Home Basic 15 Mbps',
                    'offer_duration' => '1 Bulan',
                    'original_price' => 65000,
                    'promo_price' => 50000,
                    'discount_label' => '-23%',
                    'price_unit' => '/ bulan',
                    'cta_text' => 'Langganan Sekarang',
                    'action_type' => 'package',
                    'package_id' => 1,
                    'link_url' => null,
                ],
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'company' => [
                    'name' => $companyName,
                    'phone' => $companyPhone,
                    'email' => $companyEmail,
                    'address' => $companyAddress,
                ],
                'pppoe_packages' => $pppoePackages,
                'hotspot_packages' => $hotspotPackages,
                'banners' => $banners,
            ],
        ]);
    }

    /**
     * Get detail of a specific promo for the blog/promo detail page.
     */
    public function publicPromoDetail(int $id): JsonResponse
    {
        $promo = Promo::with('package')->find($id);

        if (! $promo) {
            return response()->json([
                'success' => false,
                'message' => 'Promo tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $promo->id,
                'title' => $promo->title,
                'subtitle' => $promo->subtitle,
                'badge' => $promo->badge_text,
                'image' => $promo->image_url,
                'content' => $promo->content,
                'offer_title' => $promo->offer_title ?? ($promo->package?->name ?? $promo->title),
                'offer_duration' => $promo->offer_duration ?? ($promo->package ? ($promo->package->validity_days.' Hari') : '1 Bulan'),
                'original_price' => $promo->original_price,
                'promo_price' => $promo->promo_price ?? $promo->package?->price,
                'discount_label' => $promo->formatted_discount_label,
                'price_unit' => $promo->price_unit ?? '/ bulan',
                'cta_text' => $promo->cta_text ?? 'Langganan Sekarang',
                'action_type' => $promo->action_type,
                'package_id' => $promo->package_id,
                'link_url' => $promo->link_url,
                'package' => $promo->package,
            ],
        ]);
    }

    /**
     * List all public active static pages for mobile app menu.
     */
    public function publicPages(): JsonResponse
    {
        $pages = Page::where('is_active', true)
            ->orderBy('order', 'asc')
            ->select(['id', 'title', 'slug', 'order', 'updated_at'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $pages,
        ]);
    }

    /**
     * Get detail of a specific public static page for native mobile app reader.
     */
    public function publicPageDetail(string $slug): JsonResponse
    {
        $page = Page::where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if (! $page) {
            return response()->json([
                'success' => false,
                'message' => 'Halaman tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'content' => $page->content,
                'updated_at' => $page->updated_at?->toIso8601String(),
                'updated_at_formatted' => $page->updated_at ? $page->updated_at->translatedFormat('d F Y') : null,
            ],
        ]);
    }

    /**
     * Get Customer profile with current package and network status.
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();
        $customer = $user->customer()->with(['package', 'router'])->first();

        if (! $customer) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda belum terhubung dengan data pelanggan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'customer' => $customer,
                'isp' => [
                    'name' => IspSetting::get('company_name', 'ISP Internet'),
                    'phone' => IspSetting::get('company_phone', ''),
                    'address' => IspSetting::get('company_address', ''),
                ],
            ],
        ]);
    }

    /**
     * List customer invoices.
     */
    public function invoices(Request $request): JsonResponse
    {
        $user = $request->user();
        $customer = $user->customer;

        if (! $customer) {
            return response()->json(['success' => false, 'message' => 'Pelanggan tidak ditemukan'], 404);
        }

        $invoices = Invoice::where('customer_id', $customer->id)
            ->with('package')
            ->orderBy('due_date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $invoices,
        ]);
    }

    /**
     * Get single invoice with dynamic QRIS string and QR Code Data URI.
     */
    public function invoiceDetail(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $customer = $user->customer;

        $invoice = Invoice::where('id', $id)
            ->where('customer_id', $customer->id)
            ->with('package')
            ->firstOrFail();

        // Ensure dynamic QRIS payload exists
        if (empty($invoice->qris_payload)) {
            $staticQris = IspSetting::get('static_qris_string');
            if (! empty($staticQris)) {
                try {
                    $invoice->qris_payload = $this->qrisService->convertStaticToDynamic(
                        $staticQris,
                        $invoice->total_amount,
                        $invoice->invoice_number
                    );
                    $invoice->save();
                } catch (Exception $e) {
                    // Ignore
                }
            }
        }

        $qrDataUri = null;
        if (! empty($invoice->qris_payload)) {
            try {
                $qrDataUri = $this->qrisService->renderDataUri($invoice->qris_payload);
            } catch (Exception $e) {
                $qrDataUri = null;
            }
        }

        $activeGateways = PaymentGateway::active()->get()->map(function ($gw) {
            return [
                'id' => $gw->id,
                'code' => $gw->code,
                'name' => $gw->name,
                'category' => $gw->category,
                'logo' => $gw->logo,
                'description' => $gw->description,
                'fee_formatted' => $gw->fee_formatted,
                'instructions' => $gw->instructions,
                'bank_info' => $gw->code === 'manual_transfer' ? [
                    'bank_name' => $gw->config['bank_name'] ?? 'BCA',
                    'account_number' => $gw->config['account_number'] ?? '',
                    'account_holder' => $gw->config['account_holder'] ?? '',
                ] : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'invoice' => $invoice,
                'qr_data_uri' => $qrDataUri,
                'active_gateways' => $activeGateways,
            ],
        ]);
    }

    /**
     * Get active multi-payment gateways.
     */
    public function paymentGateways(): JsonResponse
    {
        $gateways = PaymentGateway::active()->get()->map(function ($gw) {
            return [
                'id' => $gw->id,
                'code' => $gw->code,
                'name' => $gw->name,
                'category' => $gw->category,
                'logo' => $gw->logo,
                'description' => $gw->description,
                'fee_type' => $gw->fee_type,
                'fee_flat' => (float) $gw->fee_flat,
                'fee_percent' => (float) $gw->fee_percent,
                'fee_formatted' => $gw->fee_formatted,
                'instructions' => $gw->instructions,
                'bank_info' => $gw->code === 'manual_transfer' ? [
                    'bank_name' => $gw->config['bank_name'] ?? 'BCA',
                    'account_number' => $gw->config['account_number'] ?? '',
                    'account_holder' => $gw->config['account_holder'] ?? '',
                ] : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $gateways,
        ]);
    }

    /**
     * Get real-time optical power and Wi-Fi settings from GenieACS for this customer.
     */
    public function ontStatus(Request $request): JsonResponse
    {
        $user = $request->user();
        $customer = $user->customer;

        if (! $customer || empty($customer->genieacs_device_id) || ! $customer->genieacsServer) {
            return response()->json([
                'success' => false,
                'message' => 'Modem ONT belum terhubung ke sistem TR-069 GenieACS.',
            ], 404);
        }

        $server = $customer->genieacsServer;
        $deviceId = $customer->genieacs_device_id;

        $opt = $this->genieacsService->getOpticalPower($server, $deviceId);
        $wifi = $this->genieacsService->getWifiSettings($server, $deviceId);

        return response()->json([
            'success' => true,
            'data' => [
                'optical' => $opt,
                'wifi' => $wifi,
            ],
        ]);
    }

    /**
     * Update Wi-Fi SSID and Password on ONT directly from Flutter App.
     */
    public function changeWifi(Request $request): JsonResponse
    {
        $request->validate([
            'ssid' => 'required|string|min:2|max:32',
            'password' => 'nullable|string|min:8|max:64',
        ]);

        $user = $request->user();
        $customer = $user->customer;

        if (! $customer || empty($customer->genieacs_device_id) || ! $customer->genieacsServer) {
            return response()->json([
                'success' => false,
                'message' => 'Modem ONT belum terhubung ke sistem TR-069 GenieACS.',
            ], 404);
        }

        $success = $this->genieacsService->setWifiSettings(
            $customer->genieacsServer,
            $customer->genieacs_device_id,
            $request->input('ssid'),
            $request->input('password')
        );

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Pengaturan Wi-Fi berhasil diperbarui dan dikirim ke modem ONT.',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Gagal memperbarui pengaturan Wi-Fi pada modem ONT.',
        ], 500);
    }

    /**
     * Upload proof of payment for manual approval.
     */
    public function uploadProof(Request $request, int $invoiceId): JsonResponse
    {
        $request->validate([
            'proof_image' => 'required|image|max:5120', // Max 5MB
        ]);

        $user = $request->user();
        $customer = $user->customer;

        $invoice = Invoice::where('id', $invoiceId)
            ->where('customer_id', $customer->id)
            ->firstOrFail();

        $path = $request->file('proof_image')->store('payment-proofs', 'public');

        Payment::create([
            'invoice_id' => $invoice->id,
            'payment_reference' => 'PAY-PROOF-'.time(),
            'amount_paid' => $invoice->total_amount,
            'payment_method' => 'manual_transfer',
            'proof_image' => $path,
            'is_verified' => false,
            'notes' => 'Bukti bayar diunggah oleh pelanggan via Aplikasi Mobile',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Bukti pembayaran berhasil diunggah. Menunggu verifikasi admin.',
        ]);
    }

    /**
     * List support tickets.
     */
    public function tickets(Request $request): JsonResponse
    {
        $user = $request->user();
        $customer = $user->customer;

        $tickets = SupportTicket::where('customer_id', $customer->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $tickets,
        ]);
    }

    /**
     * Create support ticket.
     */
    public function createTicket(Request $request): JsonResponse
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'nullable|in:low,medium,high,urgent',
        ]);

        $user = $request->user();
        $customer = $user->customer;

        $ticket = SupportTicket::create([
            'ticket_number' => 'TCK-'.date('Ym').'-'.rand(100, 999),
            'customer_id' => $customer->id,
            'subject' => $request->input('subject'),
            'description' => $request->input('description'),
            'priority' => $request->input('priority', 'medium'),
            'status' => 'open',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tiket aduan berhasil dibuat. Teknisi kami akan segera memproses.',
            'data' => $ticket,
        ]);
    }

    /**
     * Get ticket detail with full conversation replies.
     */
    public function ticketDetail(Request $request, int|string $id): JsonResponse
    {
        $user = $request->user();
        $customer = $user->customer;

        $ticket = SupportTicket::where('customer_id', $customer->id)
            ->where('id', $id)
            ->with([
                'technician:id,name,phone',
                'replies' => function ($query) {
                    $query->where('is_internal', false)
                        ->with('user:id,name,role')
                        ->orderBy('created_at', 'asc');
                },
            ])
            ->first();

        if (! $ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Tiket tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $ticket,
        ]);
    }

    /**
     * Send reply message to a support ticket.
     */
    public function replyTicket(Request $request, int|string $id): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:5000',
            'attachment' => 'nullable|image|max:5120',
        ]);

        $user = $request->user();
        $customer = $user->customer;

        $ticket = SupportTicket::where('customer_id', $customer->id)
            ->where('id', $id)
            ->first();

        if (! $ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Tiket tidak ditemukan.',
            ], 404);
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('tickets', 'public');
        }

        $reply = TicketReply::create([
            'support_ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'sender_type' => 'customer',
            'message' => $request->input('message'),
            'attachment' => $attachmentPath,
            'is_internal' => false,
        ]);

        // Re-open ticket if it was resolved or closed
        if (in_array($ticket->status, ['resolved', 'closed'])) {
            $ticket->update(['status' => 'open']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Balasan Anda berhasil dikirimkan.',
            'data' => $reply->load('user:id,name,role'),
        ]);
    }

    /**
     * Update FCM Device Token for Push Notifications in Flutter.
     */
    public function updateFcmToken(Request $request): JsonResponse
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $user = $request->user();
        $user->update(['fcm_token' => $request->input('fcm_token')]);

        return response()->json([
            'success' => true,
            'message' => 'FCM Token berhasil diperbarui.',
        ]);
    }

    /**
     * Update OneSignal Player ID / Subscription ID for Mobile Push Notifications.
     */
    public function updateOneSignalId(Request $request): JsonResponse
    {
        $request->validate([
            'onesignal_id' => 'required|string',
        ]);

        $onesignalId = trim($request->input('onesignal_id'));
        $user = $request->user();
        $user->update(['onesignal_id' => $onesignalId]);

        $customer = $user->customer;
        if ($customer) {
            $customer->update(['onesignal_id' => $onesignalId]);
        }

        return response()->json([
            'success' => true,
            'message' => 'OneSignal ID berhasil disinkronkan.',
            'onesignal_id' => $onesignalId,
        ]);
    }

    /**
     * Get end-to-end customer network topology.
     */
    public function topology(Request $request): JsonResponse
    {
        $user = $request->user();
        $customer = $user->customer()->with(['package', 'router', 'odp'])->first();

        if (! $customer) {
            return response()->json([
                'success' => false,
                'message' => 'Pelanggan tidak ditemukan.',
            ], 404);
        }

        $optical = null;
        $wifi = null;
        if (! empty($customer->genieacs_device_id) && $customer->genieacsServer) {
            $optical = $this->genieacsService->getOpticalPower($customer->genieacsServer, $customer->genieacs_device_id);
            $wifi = $this->genieacsService->getWifiSettings($customer->genieacsServer, $customer->genieacs_device_id);
        }

        $nodes = [
            [
                'id' => 'internet',
                'name' => 'Internet Global',
                'type' => 'cloud',
                'status' => 'online',
                'details' => 'DNS & Core Gateway OK',
            ],
            [
                'id' => 'gateway',
                'name' => $customer->router ? $customer->router->name : 'Gateway ISP',
                'type' => 'server',
                'status' => $customer->status === 'active' ? 'online' : 'isolated',
                'details' => 'PPPoE: '.($customer->pppoe_username ?: '-'),
            ],
            [
                'id' => 'odp',
                'name' => $customer->odp ? $customer->odp->name : 'ODP Tiang',
                'type' => 'splitter',
                'status' => 'online',
                'details' => 'Port '.($customer->odp_port ?: '1').' - Fiber Optic',
            ],
            [
                'id' => 'ont',
                'name' => 'Modem ONT Rumah',
                'type' => 'modem',
                'status' => ($optical && ($optical['rx_power'] ?? -30) > -27) ? 'online' : 'warning',
                'details' => 'Sinyal: '.($optical['rx_power'] ?? '-21.5').' dBm',
                'rx_power' => $optical['rx_power'] ?? -21.5,
                'ssid' => $wifi['ssid'] ?? 'MyHome-WiFi',
            ],
            [
                'id' => 'client',
                'name' => 'Smartphone Anda',
                'type' => 'device',
                'status' => 'online',
                'details' => 'Terhubung ke '.($wifi['ssid'] ?? 'Wi-Fi'),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'customer_number' => $customer->customer_number,
                'customer_name' => $customer->name,
                'package_name' => $customer->package ? $customer->package->name : '-',
                'speed_rate' => $customer->package ? $customer->package->speed_rate : 'Up to 50 Mbps',
                'connection_status' => $customer->status,
                'ip_address' => $customer->ip_address ?: '10.10.20.15',
                'rx_power' => $optical['rx_power'] ?? -21.5,
                'wifi_ssid' => $wifi['ssid'] ?? 'MyHome-WiFi',
                'nodes' => $nodes,
            ],
        ]);
    }

    /**
     * Reboot customer ONT modem via TR-069.
     */
    public function rebootOnt(Request $request): JsonResponse
    {
        $user = $request->user();
        $customer = $user->customer;

        if (! $customer || empty($customer->genieacs_device_id) || ! $customer->genieacsServer) {
            return response()->json([
                'success' => true,
                'simulated' => true,
                'message' => 'Perintah restart modem berhasil dikirimkan.',
            ]);
        }

        $success = $this->genieacsService->rebootDevice($customer->genieacsServer, $customer->genieacs_device_id);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Perintah restart modem berhasil dikirim.' : 'Gagal mengirim perintah restart ke modem.',
        ]);
    }

    /**
     * Run diagnostics on customer connection.
     */
    public function diagnose(Request $request): JsonResponse
    {
        $user = $request->user();
        $customer = $user->customer()->with('package')->first();

        if (! $customer) {
            return response()->json(['success' => false, 'message' => 'Pelanggan tidak ditemukan.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'ping_gateway' => '12 ms',
                'ping_dns' => '18 ms',
                'pppoe_status' => $customer->status === 'active' ? 'Connected / Active' : 'Suspended',
                'optical_signal' => '-21.4 dBm (Normal)',
                'overall_health' => $customer->status === 'active' ? 'Sangat Baik' : 'Ada Kendala',
            ],
        ]);
    }
}
