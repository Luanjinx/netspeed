<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Services\Billing\BillingService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generateInvoices')
                ->label('Generate Tagihan Bulan Ini')
                ->icon('heroicon-o-document-plus')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Generate Tagihan Otomatis')
                ->modalDescription('Sistem akan membuat tagihan baru untuk seluruh pelanggan aktif yang belum memiliki tagihan bulan ini.')
                ->action(function (BillingService $billing) {
                    $count = $billing->generateMonthlyInvoices();
                    Notification::make()
                        ->title("Selesai! {$count} Tagihan baru berhasil dibuat.")
                        ->success()
                        ->send();
                }),

            Action::make('runAutoIsolir')
                ->label('Jalankan Auto-Isolir')
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Jalankan Auto-Isolir Pelanggan Menunggak')
                ->modalDescription('Seluruh pelanggan yang telah melewati tanggal jatuh tempo dan belum melunasi tagihan akan di-isolir di MikroTik.')
                ->action(function (BillingService $billing) {
                    $count = $billing->processAutoIsolir();
                    Notification::make()
                        ->title("Auto-Isolir Selesai: {$count} pelanggan berhasil di-isolir.")
                        ->warning()
                        ->send();
                }),

            CreateAction::make(),
        ];
    }
}
