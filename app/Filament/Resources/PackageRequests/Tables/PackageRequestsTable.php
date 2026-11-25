<?php

namespace App\Filament\Resources\PackageRequests\Tables;

use App\Models\PackageRequest;
use App\Services\PackageRequestApprovalService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PackageRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with([
                'customer',
                'currentPackage',
                'requestedPackage',
                'invoice',
                'workOrder',
                'reviewer',
            ])->latest())
            ->defaultPaginationPageOption(10)
            ->paginationPageOptions([10, 25, 50])
            ->columns([
                TextColumn::make('request_number')
                    ->label('No. Pengajuan')
                    ->searchable()
                    ->weight('bold')
                    ->copyable(),
                TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        PackageRequest::TYPE_REGISTRATION => 'Daftar Baru',
                        PackageRequest::TYPE_UPGRADE => 'Upgrade Paket',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        PackageRequest::TYPE_REGISTRATION => 'info',
                        PackageRequest::TYPE_UPGRADE => 'primary',
                        default => 'gray',
                    }),
                TextColumn::make('customer.name')
                    ->label('Pelanggan')
                    ->searchable()
                    ->description(fn (PackageRequest $record): string => $record->customer?->phone ?? '-'),
                TextColumn::make('currentPackage.name')
                    ->label('Paket Lama')
                    ->placeholder('-'),
                TextColumn::make('requestedPackage.name')
                    ->label('Paket Diminta')
                    ->badge()
                    ->color('success'),
                TextColumn::make('payment_status')
                    ->label('Pembayaran')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        PackageRequest::PAYMENT_NOT_REQUIRED => 'Tidak Perlu',
                        PackageRequest::PAYMENT_UNPAID => 'Belum Bayar',
                        PackageRequest::PAYMENT_PAID => 'Lunas',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        PackageRequest::PAYMENT_PAID => 'success',
                        PackageRequest::PAYMENT_UNPAID => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('status')
                    ->label('Status ACC')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        PackageRequest::STATUS_PENDING => 'Menunggu ACC',
                        PackageRequest::STATUS_APPROVED => 'Disetujui',
                        PackageRequest::STATUS_REJECTED => 'Ditolak',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        PackageRequest::STATUS_PENDING => 'warning',
                        PackageRequest::STATUS_APPROVED => 'success',
                        PackageRequest::STATUS_REJECTED => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('reviewer.name')
                    ->label('Diproses Oleh')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Diajukan')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status Persetujuan')
                    ->options([
                        PackageRequest::STATUS_PENDING => 'Menunggu ACC',
                        PackageRequest::STATUS_APPROVED => 'Disetujui',
                        PackageRequest::STATUS_REJECTED => 'Ditolak',
                    ]),
                SelectFilter::make('type')
                    ->label('Jenis')
                    ->options([
                        PackageRequest::TYPE_REGISTRATION => 'Daftar Baru',
                        PackageRequest::TYPE_UPGRADE => 'Upgrade Paket',
                    ]),
                SelectFilter::make('payment_status')
                    ->label('Status Pembayaran')
                    ->options([
                        PackageRequest::PAYMENT_NOT_REQUIRED => 'Tidak Perlu Bayar',
                        PackageRequest::PAYMENT_UNPAID => 'Belum Bayar',
                        PackageRequest::PAYMENT_PAID => 'Lunas',
                    ]),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('ACC')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (PackageRequest $record): bool => $record->isPending())
                    ->requiresConfirmation()
                    ->modalHeading(fn (PackageRequest $record): string => "Setujui Pengajuan {$record->request_number}?")
                    ->modalDescription(fn (PackageRequest $record): string => $record->type === PackageRequest::TYPE_REGISTRATION
                        ? 'Pelanggan akan diaktifkan dan disinkronkan ke MikroTik (jika router sudah terpasang).'
                        : 'Paket pelanggan akan diubah ke paket baru dan diaktifkan di MikroTik.')
                    ->schema([
                        Textarea::make('admin_notes')
                            ->label('Catatan Persetujuan (Opsional)')
                            ->rows(2),
                    ])
                    ->action(function (PackageRequest $record, array $data, PackageRequestApprovalService $service): void {
                        try {
                            $service->approve($record, auth()->user(), $data['admin_notes'] ?? null);

                            Notification::make()
                                ->title('Pengajuan Disetujui')
                                ->body("Pengajuan {$record->request_number} berhasil di-ACC.")
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Gagal Menyetujui')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (PackageRequest $record): bool => $record->isPending())
                    ->modalHeading(fn (PackageRequest $record): string => "Tolak Pengajuan {$record->request_number}")
                    ->schema([
                        Textarea::make('admin_notes')
                            ->label('Alasan Penolakan')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (PackageRequest $record, array $data, PackageRequestApprovalService $service): void {
                        try {
                            $service->reject($record, auth()->user(), $data['admin_notes']);

                            Notification::make()
                                ->title('Pengajuan Ditolak')
                                ->body("Pengajuan {$record->request_number} telah ditolak.")
                                ->warning()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Gagal Menolak')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
