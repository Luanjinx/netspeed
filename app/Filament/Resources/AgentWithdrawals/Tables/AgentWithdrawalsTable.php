<?php

namespace App\Filament\Resources\AgentWithdrawals\Tables;

use App\Models\AgentWithdrawal;
use App\Services\Agent\AgentService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AgentWithdrawalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(15)
            ->columns([
                TextColumn::make('withdrawal_code')
                    ->label('Kode')
                    ->badge()
                    ->color('primary')
                    ->searchable(),

                TextColumn::make('agent.name')
                    ->label('Mitra Agen')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (AgentWithdrawal $record): string => $record->agent?->agent_code ?? '-'),

                TextColumn::make('amount')
                    ->label('Nominal Tarik')
                    ->formatStateUsing(fn ($state): string => 'Rp '.number_format((float) $state, 0, ',', '.'))
                    ->weight('bold')
                    ->sortable(),

                TextColumn::make('method')
                    ->label('Tujuan Pencairan')
                    ->formatStateUsing(fn (AgentWithdrawal $record): string => $record->method_label)
                    ->description(function (AgentWithdrawal $record): string {
                        if ($record->method === AgentWithdrawal::METHOD_CASH) {
                            return 'Ambil tunai di kantor';
                        }

                        return trim(($record->bank_or_provider ?? '').' • '.($record->account_number ?? '').' ('.($record->account_name ?? '-').')');
                    }),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        AgentWithdrawal::STATUS_PENDING => 'Menunggu Transfer',
                        AgentWithdrawal::STATUS_APPROVED => 'Selesai / Ditransfer',
                        AgentWithdrawal::STATUS_REJECTED => 'Ditolak (Refund)',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        AgentWithdrawal::STATUS_PENDING => 'warning',
                        AgentWithdrawal::STATUS_APPROVED => 'success',
                        AgentWithdrawal::STATUS_REJECTED => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('transfer_reference')
                    ->label('Ref / Alasan')
                    ->state(fn (AgentWithdrawal $record): string => $record->transfer_reference ?: ($record->rejection_reason ?: '-'))
                    ->limit(35),

                TextColumn::make('created_at')
                    ->label('Diajukan')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        AgentWithdrawal::STATUS_PENDING => 'Menunggu Transfer',
                        AgentWithdrawal::STATUS_APPROVED => 'Selesai / Ditransfer',
                        AgentWithdrawal::STATUS_REJECTED => 'Ditolak (Refund)',
                    ]),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (AgentWithdrawal $record): bool => $record->isPending())
                    ->form([
                        TextInput::make('transfer_reference')
                            ->label('No. Referensi Transfer / Keterangan')
                            ->placeholder('cth: TRF-BCA-98213 / Tunai Kasir')
                            ->maxLength(150),
                    ])
                    ->action(function (AgentWithdrawal $record, array $data): void {
                        try {
                            app(AgentService::class)->approveWithdrawal(
                                $record,
                                auth()->user(),
                                $data['transfer_reference'] ?? null
                            );

                            Notification::make()
                                ->title('Penarikan Saldo Disetujui')
                                ->body("Pengajuan {$record->withdrawal_code} ({$record->formatted_amount}) telah ditandai selesai.")
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Gagal Memproses')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('reject')
                    ->label('Tolak & Refund')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (AgentWithdrawal $record): bool => $record->isPending())
                    ->form([
                        Textarea::make('rejection_reason')
                            ->label('Alasan Penolakan')
                            ->required()
                            ->rows(3)
                            ->placeholder('cth: Nomor rekening tidak sesuai nama pemilik'),
                    ])
                    ->action(function (AgentWithdrawal $record, array $data): void {
                        try {
                            app(AgentService::class)->rejectWithdrawal(
                                $record,
                                auth()->user(),
                                (string) $data['rejection_reason']
                            );

                            Notification::make()
                                ->title('Penarikan Ditolak & Saldo Dikembalikan')
                                ->body("Saldo {$record->formatted_amount} dikembalikan ke agen {$record->agent?->name}.")
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
            ]);
    }
}
