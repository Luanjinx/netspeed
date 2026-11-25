<?php

namespace App\Services;

use App\Models\IspSetting;
use App\Models\PackageRequest;
use App\Models\User;
use App\Services\Notification\WhatsAppService;
use App\Services\Router\MikrotikApiService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

class PackageRequestApprovalService
{
    public function __construct(
        protected MikrotikApiService $mikrotikService,
        protected WhatsAppService $whatsAppService
    ) {}

    /**
     * Approve a pending registration or package upgrade request by an admin or technician.
     */
    public function approve(PackageRequest $packageRequest, User $reviewer, ?string $notes = null): PackageRequest
    {
        $this->ensureCanReview($reviewer);

        if (! $packageRequest->isPending()) {
            throw new RuntimeException('Pengajuan ini sudah diproses sebelumnya.');
        }

        if ($packageRequest->type === PackageRequest::TYPE_UPGRADE && $packageRequest->payment_status === PackageRequest::PAYMENT_UNPAID) {
            throw new RuntimeException('Tagihan prorata upgrade belum dibayar atau diverifikasi.');
        }

        $updatedRequest = DB::transaction(function () use ($packageRequest, $reviewer, $notes): PackageRequest {
            $packageRequest->update([
                'status' => PackageRequest::STATUS_APPROVED,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'admin_notes' => $notes ?: $packageRequest->admin_notes,
            ]);

            $packageRequest->load(['customer.router', 'requestedPackage', 'workOrder']);
            $customer = $packageRequest->customer;

            if ($customer) {
                if ($packageRequest->type === PackageRequest::TYPE_REGISTRATION) {
                    $customer->update([
                        'status' => 'active',
                        'install_date' => $customer->install_date ?: today(),
                        'package_id' => $packageRequest->requested_package_id ?: $customer->package_id,
                    ]);

                    if ($packageRequest->workOrder && $packageRequest->workOrder->status !== 'completed') {
                        $packageRequest->workOrder->update([
                            'status' => 'completed',
                            'completed_at' => now(),
                            'technician_id' => $packageRequest->workOrder->technician_id
                                ?: ($reviewer->role === 'technician' ? $reviewer->id : null),
                            'completion_notes' => $notes ?: $packageRequest->workOrder->completion_notes,
                        ]);
                    }

                    if ($customer->connection_type === 'pppoe' && $customer->router) {
                        try {
                            $this->mikrotikService->syncCustomerToMikrotik($customer->fresh(['package', 'router']));
                        } catch (\Throwable $e) {
                            Log::warning('MikroTik sync error on registration approval: '.$e->getMessage());
                        }
                    }
                } elseif ($packageRequest->type === PackageRequest::TYPE_UPGRADE) {
                    $targetPackage = $packageRequest->requestedPackage;

                    $customer->package_id = $packageRequest->requested_package_id;
                    $customer->promo_cycles_used = 0;
                    if ($customer->status === 'pending') {
                        $customer->status = 'active';
                    }
                    $customer->save();

                    if ($customer->router && $customer->pppoe_username && ! empty($targetPackage?->mikrotik_profile)) {
                        try {
                            $this->mikrotikService->activatePppCustomer(
                                $customer->router,
                                $customer->pppoe_username,
                                $targetPackage->mikrotik_profile
                            );
                        } catch (\Throwable $e) {
                            Log::warning('MikroTik sync error on upgrade approval: '.$e->getMessage());
                        }
                    }
                }
            }

            return $packageRequest->fresh(['customer', 'currentPackage', 'requestedPackage', 'reviewer', 'invoice', 'workOrder']);
        });

        $this->sendApprovalNotification($updatedRequest, $reviewer);

        return $updatedRequest;
    }

    /**
     * Reject a pending registration or package upgrade request by an admin or technician.
     */
    public function reject(PackageRequest $packageRequest, User $reviewer, string $reason): PackageRequest
    {
        $this->ensureCanReview($reviewer);

        if (! $packageRequest->isPending()) {
            throw new RuntimeException('Pengajuan ini sudah diproses sebelumnya.');
        }

        $updatedRequest = DB::transaction(function () use ($packageRequest, $reviewer, $reason): PackageRequest {
            $packageRequest->update([
                'status' => PackageRequest::STATUS_REJECTED,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'admin_notes' => $reason,
            ]);

            $packageRequest->load(['customer', 'workOrder', 'invoice']);

            if ($packageRequest->type === PackageRequest::TYPE_REGISTRATION) {
                if ($packageRequest->customer) {
                    $packageRequest->customer->update(['status' => 'rejected']);
                }

                if ($packageRequest->workOrder && in_array($packageRequest->workOrder->status, ['pending', 'in_progress'], true)) {
                    $packageRequest->workOrder->update([
                        'status' => 'cancelled',
                        'completion_notes' => $reason,
                    ]);
                }
            } elseif ($packageRequest->type === PackageRequest::TYPE_UPGRADE) {
                if ($packageRequest->invoice && $packageRequest->invoice->status === 'unpaid') {
                    $packageRequest->invoice->update(['status' => 'cancelled']);
                }
            }

            return $packageRequest->fresh(['customer', 'currentPackage', 'requestedPackage', 'reviewer', 'invoice', 'workOrder']);
        });

        $this->sendRejectionNotification($updatedRequest, $reason);

        return $updatedRequest;
    }

    protected function ensureCanReview(User $reviewer): void
    {
        if (! in_array($reviewer->role, ['admin', 'technician'], true)) {
            throw new InvalidArgumentException('Hanya Admin atau Teknisi yang berwenang memproses persetujuan.');
        }
    }

    protected function sendApprovalNotification(PackageRequest $packageRequest, User $reviewer): void
    {
        $customer = $packageRequest->customer;
        if (! $customer || empty($customer->phone)) {
            return;
        }

        $companyName = IspSetting::get('company_name', 'NetSpeed ISP');
        $packageName = $packageRequest->requestedPackage?->name ?? '-';
        $roleLabel = $reviewer->role === 'technician' ? 'Teknisi' : 'Admin';

        $message = $packageRequest->type === PackageRequest::TYPE_REGISTRATION
            ? "Halo {$customer->name},\n\nPendaftaran pemasangan internet {$companyName} Anda ({$packageRequest->request_number}) telah *DISETUJUI* oleh {$roleLabel} ({$reviewer->name}).\n\nPaket: *{$packageName}*\nStatus Layanan: *Aktif*\n\nTerima kasih telah berlangganan!"
            : "Halo {$customer->name},\n\nPengajuan upgrade paket {$companyName} Anda ({$packageRequest->request_number}) telah *DISETUJUI* oleh {$roleLabel} ({$reviewer->name}).\n\nPaket Baru: *{$packageName}*\nStatus: *Aktif*\n\nSelamat menikmati kecepatan internet baru Anda!";

        try {
            $this->whatsAppService->sendMessage($customer->phone, $message);
        } catch (\Throwable $e) {
            Log::warning('Gagal kirim WhatsApp approval pengajuan: '.$e->getMessage());
        }
    }

    protected function sendRejectionNotification(PackageRequest $packageRequest, string $reason): void
    {
        $customer = $packageRequest->customer;
        if (! $customer || empty($customer->phone)) {
            return;
        }

        $companyName = IspSetting::get('company_name', 'NetSpeed ISP');
        $typeLabel = $packageRequest->type === PackageRequest::TYPE_REGISTRATION ? 'pendaftaran baru' : 'upgrade paket';

        $message = "Halo {$customer->name},\n\nMohon maaf, pengajuan {$typeLabel} {$companyName} Anda ({$packageRequest->request_number}) *DITOLAK*.\n\nAlasan: {$reason}\n\nSilakan hubungi Customer Service kami untuk informasi lebih lanjut.";

        try {
            $this->whatsAppService->sendMessage($customer->phone, $message);
        } catch (\Throwable $e) {
            Log::warning('Gagal kirim WhatsApp penolakan pengajuan: '.$e->getMessage());
        }
    }
}
