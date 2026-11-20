<?php

namespace App\Filament\Resources\Invoices\Tables;

use App\Models\Invoice;
use App\Models\IspSetting;
use App\Services\Billing\BillingService;
use App\Services\Payment\QrisService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['customer']))
            ->defaultPaginationPageOption(10)
            ->paginationPageOptions([10, 25, 50])
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('No. Tagihan')
                    ->searchable()
                    ->weight('bold')
                    ->copyable(),
                TextColumn::make('customer.name')
                    ->label('Pelanggan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('period')
                    ->label('Periode')
                    ->badge(),
                TextColumn::make('total_amount')
                    ->label('Total Tagihan')
                    ->money('IDR', locale: 'id')
                    ->weight('bold')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'unpaid' => 'danger',
                        'cancelled' => 'gray',
                        default => 'warning',
                    }),
                TextColumn::make('due_date')
                    ->label('Jatuh Tempo')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('notes')
                    ->label('Catatan')
                    ->limit(35)
                    ->tooltip(fn ($record) => $record->notes)
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('paid_at')
                    ->label('Tgl Lunas')
                    ->dateTime('d M Y, H:i')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('viewQris')
                    ->label('QRIS')
                    ->icon('heroicon-o-qr-code')
                    ->color('success')
                    ->modalHeading(fn (Invoice $record) => "QRIS Dinamis Tagihan: {$record->invoice_number}")
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(function (Invoice $record, QrisService $qris) {
                        $payload = $record->qris_payload;
                        if (empty($payload)) {
                            $staticQris = IspSetting::get('static_qris_string');
                            if (! empty($staticQris)) {
                                try {
                                    $payload = $qris->convertStaticToDynamic($staticQris, $record->total_amount, $record->invoice_number);
                                    $record->update(['qris_payload' => $payload]);
                                } catch (\Exception $e) {
                                    $payload = null;
                                }
                            }
                        }

                        $svg = null;
                        if (! empty($payload)) {
                            try {
                                $svg = $qris->renderSvg($payload);
                            } catch (\Exception $e) {
                                $svg = null;
                            }
                        }

                        return view('filament.invoices.qris-modal', [
                            'svg' => $svg,
                            'total' => $record->total_amount,
                            'uniqueCode' => $record->unique_code,
                            'payload' => $payload,
                        ]);
                    }),

                Action::make('markPaid')
                    ->label('Tandai Lunas')
                    ->icon('heroicon-o-check')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Pembayaran Lunas')
                    ->modalDescription('Tagihan akan ditandai Lunas dan pelanggan akan otomatis diaktifkan kembali pada Router MikroTik.')
                    ->visible(fn (Invoice $record) => $record->status === 'unpaid')
                    ->action(function (Invoice $record, BillingService $billing) {
                        $billing->markInvoicePaid($record, 'manual_admin', 'Diverifikasi manual oleh Admin');
                        Notification::make()
                            ->title('Tagihan Berhasil Ditandai Lunas!')
                            ->body('Status pelanggan telah diperbarui dan profil internet di MikroTik telah diaktifkan.')
                            ->success()
                            ->send();
                    }),

                EditAction::make(),

                ActionGroup::make([
                    Action::make('printInvoice')
                        ->label('Cetak Invoice A4')
                        ->icon('heroicon-o-printer')
                        ->color('gray')
                        ->url(fn (Invoice $record) => route('print.invoice', $record->id), true),

                    Action::make('printThermal')
                        ->label('Cetak Struk Thermal (58/80mm)')
                        ->icon('heroicon-o-receipt-percent')
                        ->color('gray')
                        ->url(fn (Invoice $record) => route('print.thermal', $record->id), true),
                ])
                    ->label('Cetak')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->tooltip('Opsi Cetak'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
