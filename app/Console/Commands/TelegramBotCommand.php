<?php

namespace App\Console\Commands;

use App\Services\Notification\TelegramService;
use Illuminate\Console\Command;

class TelegramBotCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'telegram:bot
                            {--poll : Jalankan polling untuk memproses pesan Telegram secara langsung}
                            {--info : Tampilkan identitas bot & info webhook}
                            {--set-webhook= : Set URL webhook ke Telegram}
                            {--delete-webhook : Hapus webhook (beralih ke polling)}
                            {--test : Kirim pesan uji coba ke Chat ID Admin}';

    /**
     * The console command description.
     */
    protected $description = 'Manajemen & pengujian Telegram Bot ISP Billing';

    public function handle(TelegramService $telegram): int
    {
        $token = $telegram->getBotToken();

        if (empty($token)) {
            $this->error('Bot Token Telegram belum dikonfigurasi di Pengaturan ISP!');
            $this->info('Dapatkan token bot dari @BotFather di Telegram dan simpan pada Admin Panel.');

            return self::FAILURE;
        }

        // 1. Info Option
        if ($this->option('info')) {
            $this->info('Memeriksa info bot Telegram...');
            $me = $telegram->getMe();
            if ($me['ok'] ?? false) {
                $bot = $me['result'];
                $this->table(['Properti', 'Nilai'], [
                    ['ID Bot', $bot['id'] ?? '-'],
                    ['Nama Bot', $bot['first_name'] ?? '-'],
                    ['Username Bot', '@'.($bot['username'] ?? '-')],
                    ['Bisa Masuk Grup', ($bot['can_join_groups'] ?? false) ? 'Ya' : 'Tidak'],
                ]);
            } else {
                $this->error('Gagal mengambil data bot: '.($me['description'] ?? 'Unknown error'));
            }

            $this->newLine();
            $this->info('Memeriksa info Webhook...');
            $hookInfo = $telegram->getWebhookInfo();
            if ($hookInfo['ok'] ?? false) {
                $hook = $hookInfo['result'];
                $this->table(['Webhook Properti', 'Nilai'], [
                    ['URL Webhook', $hook['url'] ?: '(Tidak ada / Mode Polling)'],
                    ['Pending Update', $hook['pending_update_count'] ?? 0],
                    ['Error Terakhir', $hook['last_error_message'] ?? '-'],
                ]);
            }

            return self::SUCCESS;
        }

        // 2. Set Webhook
        if ($url = $this->option('set-webhook')) {
            $this->info("Mendaftarkan webhook ke: {$url} ...");
            $res = $telegram->setWebhook($url);
            if ($res['ok'] ?? false) {
                $this->info('✅ Webhook berhasil didaftarkan ke Telegram!');

                return self::SUCCESS;
            }

            $this->error('❌ Gagal set webhook: '.($res['description'] ?? 'Error'));

            return self::FAILURE;
        }

        // 3. Delete Webhook
        if ($this->option('delete-webhook')) {
            $this->info('Menghapus webhook Telegram...');
            $res = $telegram->deleteWebhook();
            if ($res['ok'] ?? false) {
                $this->info('✅ Webhook berhasil dihapus! Anda sekarang dapat menggunakan polling.');

                return self::SUCCESS;
            }

            $this->error('❌ Gagal menghapus webhook: '.($res['description'] ?? 'Error'));

            return self::FAILURE;
        }

        // 4. Test Notification
        if ($this->option('test')) {
            $adminChatId = $telegram->getAdminChatId();
            if (empty($adminChatId)) {
                $this->error('Chat ID Admin belum dikonfigurasi!');

                return self::FAILURE;
            }

            $this->info("Mengirim pesan uji coba ke Chat ID: {$adminChatId} ...");
            $msg = "🚀 <b>TEST BOT TELEGRAM ISP BILLING BERHASIL!</b>\n\n"
                ."Koneksi antara server backend ISP Billing dan Bot Telegram berjalan normal.\n"
                .'Waktu Server: '.now()->format('d/m/Y H:i:s').' WIB';

            $res = $telegram->sendToAdmin($msg);
            if ($res['ok'] ?? false) {
                $this->info('✅ Pesan berhasil terkirim ke Telegram!');

                return self::SUCCESS;
            }

            $this->error('❌ Gagal mengirim pesan: '.($res['description'] ?? 'Error'));

            return self::FAILURE;
        }

        // 5. Polling Option
        if ($this->option('poll')) {
            $this->info('🤖 Memulai Telegram Bot Long-Polling (Tekan Ctrl+C untuk berhenti)...');
            $this->info('Menghapus webhook aktif terlebih dahulu...');
            $telegram->deleteWebhook();

            $offset = 0;
            while (true) {
                try {
                    $res = $telegram->getUpdates($offset, 20, 5);
                    if (($res['ok'] ?? false) && ! empty($res['result'])) {
                        foreach ($res['result'] as $upd) {
                            $updateId = $upd['update_id'];
                            $offset = $updateId + 1;

                            $user = $upd['message']['from']['first_name'] ?? 'User';
                            $text = $upd['message']['text'] ?? ($upd['callback_query']['data'] ?? '[Event]');
                            $this->line('[<fg=cyan>'.date('H:i:s')."</>] Pesan dari <fg=yellow>{$user}</>: <fg=green>{$text}</>");

                            $telegram->handleUpdate($upd);
                        }
                    }
                } catch (\Throwable $e) {
                    $this->error('Error polling: '.$e->getMessage());
                    sleep(2);
                }
                usleep(500000); // 0.5s pause
            }
        }

        $this->info('Gunakan salah satu flag: --poll, --info, --set-webhook=<url>, --delete-webhook, atau --test');

        return self::SUCCESS;
    }
}
