<?php

namespace App\Filament\Resources\SupportTickets\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class RepliesRelationManager extends RelationManager
{
    protected static string $relationship = 'replies';

    protected static ?string $title = 'Percakapan & Balasan Tiket';

    protected static ?string $modelLabel = 'Balasan Tiket';

    protected static ?string $pluralModelLabel = 'Percakapan & Balasan Tiket';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('message')
                    ->label('Pesan Balasan / Catatan Penanganan')
                    ->required()
                    ->rows(4)
                    ->placeholder('Tuliskan respon untuk pelanggan atau arahan untuk teknisi lapangan...'),
                FileUpload::make('attachment')
                    ->label('Lampiran Gambar / Bukti Foto (Opsional)')
                    ->disk('public')
                    ->directory('tickets')
                    ->image()
                    ->maxSize(5120),
                Toggle::make('is_internal')
                    ->label('Catatan Internal Saja (Disembunyikan dari Pelanggan)')
                    ->helperText('Jika diaktifkan, balasan ini hanya dapat dilihat oleh tim Admin dan Teknisi.')
                    ->default(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('message')
            ->defaultSort('created_at', 'asc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
                TextColumn::make('sender_type')
                    ->label('Peran')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'admin' => 'Customer Care',
                        'technician' => 'Teknisi Lapangan',
                        'customer' => 'Pelanggan',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'success',
                        'technician' => 'info',
                        'customer' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('user.name')
                    ->label('Nama Pengirim')
                    ->placeholder('Sistem'),
                TextColumn::make('message')
                    ->label('Pesan')
                    ->wrap()
                    ->searchable(),
                ImageColumn::make('attachment')
                    ->label('Lampiran')
                    ->disk('public')
                    ->circular(),
                IconColumn::make('is_internal')
                    ->label('Internal')
                    ->boolean(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Kirim Balasan Baru')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->mutateFormDataUsing(function (array $data): array {
                        $user = Auth::user();
                        $data['user_id'] = $user->id;
                        $data['sender_type'] = $user->role === 'technician' ? 'technician' : 'admin';

                        return $data;
                    })
                    ->after(function ($livewire): void {
                        // Update ticket status to in_progress if currently open
                        $ticket = $livewire->getOwnerRecord();
                        if ($ticket->status === 'open') {
                            $ticket->update(['status' => 'in_progress']);
                        }
                    }),
            ])
            ->recordActions([
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
