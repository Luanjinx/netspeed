<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NetworkTestController extends Controller
{
    /**
     * Latency ping endpoint for Flutter mobile app.
     */
    public function ping(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'timestamp' => microtime(true),
            'server_time' => now()->toIso8601String(),
        ]);
    }

    /**
     * Stream binary chunk for client download speed test.
     */
    public function downloadTest(Request $request): StreamedResponse
    {
        $sizeMb = min(max((int) $request->input('size', 5), 1), 25); // 1 - 25 MB
        $chunkSize = 1024 * 1024; // 1 MB per chunk

        return response()->stream(function () use ($sizeMb, $chunkSize) {
            $buffer = str_repeat('0', $chunkSize);
            for ($i = 0; $i < $sizeMb; $i++) {
                echo $buffer;
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }
        }, 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Length' => $sizeMb * $chunkSize,
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    /**
     * Upload speed test endpoint for Flutter client.
     */
    public function uploadTest(Request $request): JsonResponse
    {
        $receivedBytes = strlen($request->getContent());

        return response()->json([
            'success' => true,
            'received_bytes' => $receivedBytes,
            'timestamp' => microtime(true),
        ]);
    }
}
