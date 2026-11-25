<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IspSetting;
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
        $configuredSecret = trim((string) IspSetting::get('telegram_webhook_secret', ''));
        $providedSecret = (string) $request->header('X-Telegram-Bot-Api-Secret-Token', '');

        if ($configuredSecret === '' && app()->environment('production')) {
            return response()->json(['ok' => false, 'message' => 'Webhook secret not configured'], 403);
        }

        if ($configuredSecret !== '' && ! hash_equals($configuredSecret, $providedSecret)) {
            Log::warning('Unauthorized Telegram webhook request rejected.', ['ip' => $request->ip()]);

            return response()->json(['ok' => false, 'message' => 'Unauthorized'], 401);
        }

        $payload = $request->all();

        if (empty($payload)) {
            return response()->json(['ok' => false, 'message' => 'Empty payload'], 400);
        }

        try {
            $telegramService->handleUpdate($payload);
        } catch (Exception $e) {
            Log::error('Telegram webhook handler exception: '.$e->getMessage(), [
                'update_id' => $payload['update_id'] ?? null,
            ]);
        }

        // Telegram requires HTTP 200 response to acknowledge receipt
        return response()->json(['ok' => true]);
    }
}
