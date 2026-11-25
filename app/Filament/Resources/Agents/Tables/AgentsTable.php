<?php

namespace App\Filament\Resources\Agents\Tables;

use App\Models\Agent;
use App\Services\Agent\AgentService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class AgentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(10)
            ->columns([
                TextColumn::make('agent_code')
                    ->label('Kode')
                    ->badge()
                    ->color('primary')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('name')
                    ->label('Nama Agen / Loket')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (Agent $record): ?string => $record->address),

                TextColumn::make('phone')
                    ->label('WhatsApp')
                    ->url(fn (Agent $record) => $record->phone ? 'https://wa.me/'.preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $record->phone)) : null, true)
                    ->color('success')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->placeholder('-'),

                TextColumn::make('balance')
                    ->label('Saldo Deposit')
                    ->badge()
                    ->formatStateUsing(fn ($state) => 'Rp '.number_format((float) $state, 0, ',', '.'))
                    ->color(fn ($state) => (float) $state > 0 ? 'success' : 'danger')
                    ->sortable(),

                TextColumn::make('commission_per_transaction')
                    ->label('Komisi / Fee')
                    ->formatStateUsing(fn ($state) => 'Rp '.number_format((float) $state, 0, ',', '.'))
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),

                TextColumn::make('approval_status')
                    ->label('Status Pengajuan')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        Agent::STATUS_PENDING => 'Menunggu ACC',
                        Agent::STATUS_REJECTED => 'Ditolak',
                        default => 'Disetujui',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        Agent::STATUS_PENDING => 'warning',
                        Agent::STATUS_REJECTED => 'danger',
                        default => 'success',
                    })
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Bergabung')
                    ->dateTime('d M Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('approval_status')
                    ->label('Status Pengajuan')
                    ->options([
                        Agent::STATUS_PENDING => 'Menunggu ACC',
                        Agent::STATUS_APPROVED => 'Disetujui',
                        Agent::STATUS_REJECTED => 'Ditolak',
                    ]),
            ])
            ->recordActions([
                // Approve Registration Action
                Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Agent $record): bool => $record->approval_status !== Agent::STATUS_APPROVED)
                    ->form([
                        TextInput::make('commission_per_transaction')
                            ->label('Komisi per Transaksi PPPoE')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(fn (Agent $record): float => (float) ($record->commission_per_transaction ?: 2000))
                            ->required()
                            ->minValue(0),
                        TextInput::make('initial_balance')
                            ->label('Saldo Deposit Awal (Opsional)')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->minValue(0)
                            ->helperText('Isi jika mitra sudah menyetor deposit awal.'),
                    ])
                    ->action(function (Agent $record, array $data): void {
                        try {
                            $record->update([
                                'approval_status' => Agent::STATUS_APPROVED,
                                'is_active' => true,
                                'commission_per_transaction' => (float) $data['commission_per_transaction'],
                                'rejection_reason' => null,
                                'approved_by' => auth()->id(),
                                'approved_at' => now(),
                            ]);

                            $initialBalance = (float) ($data['initial_balance'] ?? 0);
                            if ($initialBalance > 0) {
                                app(AgentService::class)->topUpBalance(
                                    $record->fresh(),
                                    $initialBalance,
                                    'Deposit awal persetujuan mitra agen',
                                    auth()->user()
                                );
                            }

                            Notification::make()
                                ->title('Mitra Agen Disetujui')
                                ->body("Akun Mitra Agen {$record->name} ({$record->agent_code}) kini aktif.")
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Gagal Menyetujui Agen')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                // Reject Registration Action
                Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Agent $record): bool => $record->approval_status === Agent::STATUS_PENDING)
                    ->form([
                        Textarea::make('rejection_reason')
                            ->label('Alasan Penolakan')
                            ->required()
                            ->rows(3)
                            ->placeholder('cth: Alamat loket kurang lengkap / area belum terjangkau.'),
                    ])
                    ->action(function (Agent $record, array $data): void {
                        $record->update([
                            'approval_status' => Agent::STATUS_REJECTED,
                            'is_active' => false,
                            'rejection_reason' => $data['rejection_reason'],
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Pengajuan Agen Ditolak')
                            ->body("Pengajuan {$record->name} telah ditolak.")
                            ->warning()
                            ->send();
                    }),

                // Top-Up Action
                Action::make('topUp')
                    ->label('Top-Up')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->form([
                        TextInput::make('amount')
                            ->label('Nominal Top-Up Deposit')
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->minValue(1000)
                            ->placeholder('100000')
                            ->helperText('Saldo akan langsung bertambah dan dicatat di mutasi riwayat.'),
                        TextInput::make('notes')
                            ->label('Catatan Setoran / Keterangan')
                            ->placeholder('cth: Setoran tunai di kasir / transfer BCA')
                            ->maxLength(255),
                    ])
                    ->action(function (Agent $record, array $data) {
                        try {
                            $service = app(AgentService::class);
                            $tx = $service->topUpBalance($record, (float) $data['amount'], $data['notes'] ?? null, auth()->user());

                            Notification::make()
                                ->title('Top-Up Berhasil!')
                                ->body("Saldo Agen {$record->name} berhasil ditambah sebesar Rp ".number_format((float) $data['amount'], 0, ',', '.').". Saldo sekarang: {$record->fresh()->formatted_balance}")
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Gagal Top-Up')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                // History Action
                Action::make('history')
                    ->label('Riwayat')
                    ->icon('heroicon-o-clock')
                    ->color('info')
                    ->modalHeading(fn (Agent $record) => "Riwayat Saldo: {$record->name} ({$record->agent_code})")
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(function (Agent $record) {
                        $transactions = $record->transactions()->take(10)->get();

                        if ($transactions->isEmpty()) {
                            return new HtmlString('<div style="text-align: center; padding: 2rem; color: var(--muted);">Belum ada riwayat transaksi saldo.</div>');
                        }

                        $rows = '';
                        foreach ($transactions as $t) {
                            $isPlus = in_array($t->type, ['deposit', 'refund', 'commission']);
                            $sign = $isPlus ? '+' : '-';
                            $color = $isPlus ? '#00e07e' : '#f27b7b';
                            $formattedAmount = $sign.' Rp '.number_format((float) $t->amount, 0, ',', '.');
                            $time = $t->created_at->format('d/m/Y H:i');

                            $rows .= "<tr>
                                <td style='padding: 0.5rem; font-size: 0.75rem; color: var(--muted); border-bottom: 1px solid var(--border);'>{$time}</td>
                                <td style='padding: 0.5rem; font-size: 0.8125rem; font-weight: 700; color: var(--text); border-bottom: 1px solid var(--border);'>{$t->description}</td>
                                <td style='padding: 0.5rem; font-size: 0.8125rem; font-weight: 800; color: {$color}; text-align: right; border-bottom: 1px solid var(--border);'>{$formattedAmount}</td>
                                <td style='padding: 0.5rem; font-size: 0.75rem; color: var(--muted); text-align: right; border-bottom: 1px solid var(--border);'>Rp ".number_format((float) $t->balance_after, 0, ',', '.').'</td>
                            </tr>';
                        }

                        return new HtmlString("
                            <div style='overflow-x: auto;'>
                                <table style='width: 100%; text-align: left; border-collapse: collapse;'>
                                    <thead>
                                        <tr style='background: var(--surface2);'>
                                            <th style='padding: 0.5rem; font-size: 0.75rem; color: var(--muted);'>Waktu</th>
                                            <th style='padding: 0.5rem; font-size: 0.75rem; color: var(--muted);'>Keterangan</th>
                                            <th style='padding: 0.5rem; font-size: 0.75rem; color: var(--muted); text-align: right;'>Nominal</th>
                                            <th style='padding: 0.5rem; font-size: 0.75rem; color: var(--muted); text-align: right;'>Sisa Saldo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {$rows}
                                    </tbody>
                                </table>
                            </div>
                        ");
                    }),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
