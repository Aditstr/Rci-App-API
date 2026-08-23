<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ComplianceFlagResource\Pages;
use App\Models\ComplianceFlag;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class ComplianceFlagResource extends Resource
{
    protected static ?string $model = ComplianceFlag::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';
    protected static ?string $navigationGroup = 'Kepatuhan & Risiko';
    protected static ?string $navigationLabel = 'Laporan Pembayaran';
    protected static ?string $modelLabel = 'Laporan Kepatuhan';
    protected static ?string $pluralModelLabel = 'Laporan Kepatuhan';
    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        $count = ComplianceFlag::whereIn('status', ['pending', 'reviewing'])->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Konteks Laporan')
                ->schema([
                    Infolists\Components\TextEntry::make('legalCase.case_number')->label('Nomor Kasus')->default('—'),
                    Infolists\Components\TextEntry::make('legalCase.title')->label('Judul Kasus')->default('—'),
                    Infolists\Components\TextEntry::make('subject.name')->label('Expert Terlapor')->default('Akun telah dihapus'),
                    Infolists\Components\TextEntry::make('subject.email')->label('Email Expert')->default('—'),
                    Infolists\Components\TextEntry::make('reporter.name')->label('Pelapor')->default('Deteksi otomatis'),
                    Infolists\Components\TextEntry::make('created_at')->label('Waktu')->dateTime(),
                ])->columns(3),

            Infolists\Components\Section::make('Penilaian Risiko')
                ->schema([
                    Infolists\Components\TextEntry::make('source')
                        ->label('Sumber')
                        ->badge()
                        ->formatStateUsing(fn (string $state): string => $state === 'automatic' ? 'Deteksi otomatis' : 'Laporan klien'),
                    Infolists\Components\TextEntry::make('severity')
                        ->label('Level')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'high' => 'danger',
                            'medium' => 'warning',
                            default => 'gray',
                        }),
                    Infolists\Components\TextEntry::make('risk_score')->label('Skor Risiko')->suffix('/100'),
                    Infolists\Components\TextEntry::make('matched_signals')
                        ->label('Sinyal')
                        ->formatStateUsing(fn ($state): string => is_array($state) ? implode(', ', $state) : '—'),
                    Infolists\Components\TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'pending' => 'warning',
                            'reviewing' => 'info',
                            'confirmed' => 'danger',
                            'dismissed' => 'success',
                            default => 'gray',
                        }),
                ])->columns(3),

            Infolists\Components\Section::make('Bukti untuk Peninjauan Internal')
                ->description('Isi pesan dienkripsi di database dan hanya ditampilkan kepada admin untuk pemeriksaan kepatuhan.')
                ->schema([
                    Infolists\Components\TextEntry::make('evidence_text')
                        ->label('Isi Pesan')
                        ->prose()
                        ->columnSpanFull(),
                    Infolists\Components\TextEntry::make('reporter_notes')
                        ->label('Keterangan Klien')
                        ->default('—')
                        ->columnSpanFull(),
                    Infolists\Components\TextEntry::make('review_notes')
                        ->label('Catatan Reviewer')
                        ->default('—')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('legalCase.case_number')
                    ->label('Kasus')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('subject.name')
                    ->label('Expert')
                    ->searchable()
                    ->description(fn (ComplianceFlag $record): string => $record->subject?->role ?? 'akun dihapus'),
                Tables\Columns\TextColumn::make('source')
                    ->label('Sumber')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'automatic' ? 'Otomatis' : 'Klien')
                    ->color(fn (string $state): string => $state === 'automatic' ? 'info' : 'warning'),
                Tables\Columns\TextColumn::make('severity')
                    ->label('Level')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'high' => 'danger',
                        'medium' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('risk_score')->label('Skor')->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'reviewing' => 'info',
                        'confirmed' => 'danger',
                        'dismissed' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')->label('Dilaporkan')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'pending' => 'Menunggu',
                    'reviewing' => 'Sedang ditinjau',
                    'confirmed' => 'Terbukti',
                    'dismissed' => 'Tidak terbukti',
                ]),
                Tables\Filters\SelectFilter::make('source')->options([
                    'automatic' => 'Deteksi otomatis',
                    'user_report' => 'Laporan klien',
                ]),
                Tables\Filters\SelectFilter::make('severity')->options([
                    'high' => 'Tinggi',
                    'medium' => 'Sedang',
                    'low' => 'Rendah',
                ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Lihat bukti'),
                Action::make('review')
                    ->label('Mulai tinjau')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->action(function (ComplianceFlag $record): void {
                        $record->update([
                            'status' => 'reviewing',
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                        ]);
                    })
                    ->visible(fn (ComplianceFlag $record): bool => $record->status === 'pending'),
                Action::make('confirm_suspend')
                    ->label('Terbukti & suspend')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi pelanggaran dan suspend akun')
                    ->modalDescription('Semua token login expert akan dicabut. Pastikan bukti sudah diperiksa dan catat dasar keputusan untuk proses keberatan.')
                    ->form([
                        Forms\Components\Textarea::make('review_notes')
                            ->label('Dasar keputusan')
                            ->required()
                            ->maxLength(2000),
                    ])
                    ->action(function (ComplianceFlag $record, array $data): void {
                        DB::transaction(function () use ($record, $data): void {
                            $record->update([
                                'status' => 'confirmed',
                                'review_notes' => $data['review_notes'],
                                'reviewed_by' => auth()->id(),
                                'reviewed_at' => now(),
                            ]);

                            if ($record->subject_user_id) {
                                User::whereKey($record->subject_user_id)
                                    ->update(['is_active' => DB::raw('FALSE')]);
                                $record->subject?->tokens()->delete();
                            }
                        });

                        FilamentNotification::make()
                            ->title('Pelanggaran dikonfirmasi')
                            ->body('Akun expert dinonaktifkan dan seluruh token akses telah dicabut.')
                            ->danger()
                            ->send();
                    })
                    ->visible(fn (ComplianceFlag $record): bool => in_array($record->status, ['pending', 'reviewing'], true)),
                Action::make('dismiss')
                    ->label('Tidak terbukti')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('review_notes')
                            ->label('Alasan penutupan')
                            ->required()
                            ->maxLength(2000),
                    ])
                    ->action(function (ComplianceFlag $record, array $data): void {
                        $record->update([
                            'status' => 'dismissed',
                            'review_notes' => $data['review_notes'],
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                        ]);
                    })
                    ->visible(fn (ComplianceFlag $record): bool => in_array($record->status, ['pending', 'reviewing'], true)),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListComplianceFlags::route('/'),
            'view' => Pages\ViewComplianceFlag::route('/{record}'),
        ];
    }
}
