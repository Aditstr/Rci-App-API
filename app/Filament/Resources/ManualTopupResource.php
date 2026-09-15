<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ManualTopupResource\Pages;
use App\Models\Payment;
use App\Services\EscrowService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ManualTopupResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Keuangan';

    protected static ?string $navigationLabel = 'Top-up Manual';

    protected static ?string $modelLabel = 'Top-up Manual';

    protected static ?string $pluralModelLabel = 'Top-up Manual';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        $count = Payment::where('payment_type', 'topup')
            ->where('payment_method', 'manual_transfer')
            ->where('status', 'pending_proof')
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->where('payment_type', 'topup')
            ->where('payment_method', 'manual_transfer');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('#')->sortable(),
                Tables\Columns\TextColumn::make('user.name')->label('User')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('user.email')->label('Email')->toggleable(),
                Tables\Columns\TextColumn::make('amount')->label('Nominal')->money('IDR')->sortable(),
                Tables\Columns\TextColumn::make('bank_name')->label('Bank Pengirim')->badge(),
                Tables\Columns\TextColumn::make('sender_name')->label('Pengirim'),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending_proof' => 'warning',
                        'completed' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending_proof' => 'Menunggu Verifikasi',
                        'completed' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('created_at')->label('Dikirim')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'pending_proof' => 'Menunggu Verifikasi',
                    'completed' => 'Disetujui',
                    'rejected' => 'Ditolak',
                ]),
            ])
            ->actions([
                Action::make('viewProof')
                    ->label('Lihat Bukti')
                    ->icon('heroicon-o-photo')
                    ->color('info')
                    ->modalHeading('Bukti Transfer')
                    ->modalContent(function (Payment $record) {
                        if (! $record->proof_path || ! Storage::disk(config('filesystems.default', 'local'))->exists($record->proof_path)) {
                            // try s3
                            $disk = Storage::disk('s3')->exists($record->proof_path) ? 's3' : 'local';
                            if (! Storage::disk($disk)->exists($record->proof_path)) {
                                return view('filament.components.proof-missing');
                            }
                            $disk = Storage::disk($disk);
                        } else {
                            $disk = Storage::disk(config('filesystems.default', 'local'));
                        }

                        // generate temporary URL for s3, or fallback to download route
                        $url = null;
                        try {
                            if (config('filesystems.disks.s3.key')) {
                                $url = Storage::disk('s3')->temporaryUrl($record->proof_path, now()->addMinutes(10));
                            } else {
                                $url = Storage::disk('local')->exists($record->proof_path)
                                    ? route('manual-proof.download', $record->id)
                                    : null;
                            }
                        } catch (\Throwable $e) {
                            $url = route('manual-proof.download', $record->id);
                        }

                        // ponytail: simplest blade with img tag
                        return view('filament.components.proof-preview', ['url' => $url, 'record' => $record]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
                Action::make('approve')
                    ->label('Setujui & Isi Saldo')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Setujui Top-up Manual')
                    ->modalDescription('Saldo user akan langsung ditambahkan. Pastikan bukti & mutasi bank sudah cocok.')
                    ->action(function (Payment $record): void {
                        if ($record->status !== 'pending_proof') {
                            Notification::make()->title('Sudah diproses')->warning()->send();
                            return;
                        }
                        DB::transaction(function () use ($record): void {
                            $record->refresh();
                            if ($record->status !== 'pending_proof') return;

                            // credit wallet via directTopUp (handles lock + transaction)
                            app(EscrowService::class)->directTopUp($record->user, (float) $record->amount);

                            $record->update([
                                'status' => 'completed',
                                'verified_by' => auth()->id(),
                                'verified_at' => now(),
                                'paid_at' => now(),
                            ]);
                        });
                        Notification::make()->title('Top-up disetujui')->body('Saldo Rp ' . number_format((float) $record->amount, 0, ',', '.') . ' telah ditambahkan.')->success()->send();
                    })
                    ->visible(fn (Payment $record): bool => $record->status === 'pending_proof'),
                Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Tolak Bukti Transfer')
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')->label('Alasan penolakan')->required()->maxLength(500),
                    ])
                    ->action(function (Payment $record, array $data): void {
                        $record->update([
                            'status' => 'rejected',
                            'rejection_reason' => $data['rejection_reason'],
                            'verified_by' => auth()->id(),
                            'verified_at' => now(),
                        ]);
                        Notification::make()->title('Top-up ditolak')->warning()->send();
                    })
                    ->visible(fn (Payment $record): bool => $record->status === 'pending_proof'),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListManualTopups::route('/'),
        ];
    }
}
