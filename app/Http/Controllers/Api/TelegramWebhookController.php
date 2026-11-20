<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Notification\TelegramService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    /**
     * Handle incoming webhook updates from Telegram Bot API.
     */
    public function handle(Request $request, TelegramService $telegramService): JsonResponse
    {
        $payload = $request->all();

        if (empty($payload)) {
            return response()->json(['ok' => false, 'message' => 'Empty payload'], 400);
        }

        try {
            $telegramService->handleUpdate($payload);
        } catch (Exception $e) {
            Log::error('Telegram webhook handler exception: '.$e->getMessage(), [
                'payload' => $payload,
            ]);
        }

        // Telegram requires HTTP 200 response to acknowledge receipt
        return response()->json(['ok' => true]);
    }
}
