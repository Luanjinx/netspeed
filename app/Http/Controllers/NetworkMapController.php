<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\IspSetting;
use App\Models\Odp;
use App\Services\Router\MikrotikApiService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NetworkMapController extends Controller
{
    /**
     * Get all network mapping data (Office, ODPs, and Customers with links and cable paths).
     */
    public function getMapData(): JsonResponse
    {
        $office = IspSetting::getOfficeLocation();

        $odps = Odp::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get()
            ->map(function ($odp) {
                return [
                    'id' => $odp->id,
                    'name' => $odp->name,
                    'code' => $odp->code,
                    'lat' => (float) $odp->latitude,
                    'lng' => (float) $odp->longitude,
                    'total_ports' => (int) $odp->total_ports,
                    'occupied_ports' => $odp->customers()->count(),
                    'splitter_ratio' => $odp->splitter_ratio,
                    'status' => $odp->status,
                    'address' => $odp->address ?: '',
                ];
            });

        $customers = Customer::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with(['package', 'router', 'odp'])
            ->get()
            ->map(function ($cust) {
                return [
                    'id' => $cust->id,
                    'customer_number' => $cust->customer_number,
                    'name' => $cust->name,
                    'phone' => $cust->phone,
                    'address' => $cust->address ?: '',
                    'status' => $cust->status,
                    'lat' => (float) $cust->latitude,
                    'lng' => (float) $cust->longitude,
                    'cable_path' => $cust->cable_path,
                    'package_name' => $cust->package?->name ?? 'Tanpa Paket',
                    'speed' => $cust->package?->speed_rate ?? '-',
                    'due_date' => $cust->due_date_day ? "Tgl {$cust->due_date_day}" : '-',
                    'pppoe_username' => $cust->pppoe_username,
                    'router_id' => $cust->router_id,
                    'odp_id' => $cust->odp_id,
                    'odp_name' => $cust->odp?->name,
                    'odp_port' => $cust->odp_port,
                    'odp_lat' => $cust->odp?->latitude ? (float) $cust->odp->latitude : null,
                    'odp_lng' => $cust->odp?->longitude ? (float) $cust->odp->longitude : null,
                ];
            });

        return response()->json([
            'success' => true,
            'office' => $office,
            'odps' => $odps,
            'customers' => $customers,
        ]);
    }

    /**
     * Save custom fiber cable path (polyline) for a customer.
     */
    public function saveCablePath(Request $request): JsonResponse
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'cable_path' => 'nullable|array',
        ]);

        $customer = Customer::findOrFail($request->input('customer_id'));
        $customer->update([
            'cable_path' => $request->input('cable_path'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Jalur kabel fiber optik berhasil disimpan!',
            'cable_path' => $customer->cable_path,
        ]);
    }

    /**
     * Get real-time PPPoE traffic for customer from MikroTik.
     */
    public function getCustomerTraffic(int $customerId, MikrotikApiService $mikrotik): JsonResponse
    {
        $customer = Customer::with('router')->findOrFail($customerId);
        $router = $customer->router;

        if (! $router || empty($customer->pppoe_username)) {
            return response()->json([
                'online' => false,
                'message' => 'Router atau username PPPoE belum diset pada pelanggan.',
            ]);
        }

        try {
            if (! $mikrotik->connect($router)) {
                return response()->json([
                    'online' => false,
                    'message' => 'Tidak dapat terhubung ke MikroTik.',
                ]);
            }

            // Check if user is active in /ppp/active
            $activeList = $mikrotik->comm('/ppp/active/print', [
                '?name' => $customer->pppoe_username,
            ]);

            $active = $activeList['!re'][0] ?? null;

            if (! $active) {
                $mikrotik->disconnect();

                return response()->json([
                    'online' => false,
                    'message' => 'Pelanggan sedang OFFLINE (Tidak ada sesi PPPoE aktif).',
                    'username' => $customer->pppoe_username,
                ]);
            }

            $uptime = $active['uptime'] ?? '-';
            $ipAddress = $active['address'] ?? '-';
            $callerId = $active['caller-id'] ?? '-'; // MAC address

            // Try to monitor traffic on interface <pppoe-username>
            $interfaceName = "<pppoe-{$customer->pppoe_username}>";
            $traffic = $mikrotik->comm('/interface/monitor-traffic', [
                '=interface='.$interfaceName,
                '=once=',
            ]);

            $trafficData = $traffic['!re'][0] ?? null;
            $rxBps = isset($trafficData['rx-bits-per-second']) ? (int) $trafficData['rx-bits-per-second'] : 0;
            $txBps = isset($trafficData['tx-bits-per-second']) ? (int) $trafficData['tx-bits-per-second'] : 0;

            $mikrotik->disconnect();

            return response()->json([
                'online' => true,
                'username' => $customer->pppoe_username,
                'ip_address' => $ipAddress,
                'mac_address' => $callerId,
                'uptime' => $uptime,
                'rx_rate' => $this->formatBitsPerSecond($rxBps),
                'tx_rate' => $this->formatBitsPerSecond($txBps),
                'rx_raw' => $rxBps,
                'tx_raw' => $txBps,
            ]);
        } catch (Exception $e) {
            $mikrotik->disconnect();

            return response()->json([
                'online' => false,
                'message' => 'Error: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Format bits per second to readable kbps / Mbps.
     */
    protected function formatBitsPerSecond(int $bps): string
    {
        if ($bps >= 1000000) {
            return number_format($bps / 1000000, 2).' Mbps';
        } elseif ($bps >= 1000) {
            return number_format($bps / 1000, 1).' kbps';
        }

        return "{$bps} bps";
    }
}
