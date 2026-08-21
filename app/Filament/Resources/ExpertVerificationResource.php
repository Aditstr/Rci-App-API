<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExpertVerificationResource\Pages;
use App\Models\ExpertProfile;
use App\Notifications\ExpertApprovedNotification;
use App\Notifications\ExpertRejectedNotification;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Support\Facades\Storage;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ExpertVerificationResource extends Resource
{
    protected static ?string $model = ExpertProfile::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'Manajemen Pengguna';
    protected static ?string $navigationLabel = 'Verifikasi Expert';
    protected static ?string $modelLabel = 'Verifikasi Expert';
    protected static ?string $pluralModelLabel = 'Verifikasi Expert';
    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return (string) ExpertProfile::where('verification_status', 'pending')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Pengguna')
                    ->schema([
                        Infolists\Components\TextEntry::make('user.name')
                            ->label('Nama Lengkap')
                            ->default('—'),
                        Infolists\Components\TextEntry::make('user.email')
                            ->label('Email')
                            ->default('—'),
                        Infolists\Components\TextEntry::make('user.role')
                            ->label('Role')
                            ->badge()
                            ->color(fn (?string $state): string => match ($state) {
                                'lawyer' => 'info',
                                'paralegal' => 'success',
                                default => 'gray',
                            }),
                    ])->columns(3),

                Infolists\Components\Section::make('Dokumen yang Diupload')
                    ->schema([
                        Infolists\Components\ImageEntry::make('ktp_path')
                            ->label('KTP')
                            ->hidden(fn (?ExpertProfile $record) => empty($record->ktp_path))
                            ->height(200)
                            ->extraImgAttributes(['style' => 'object-fit: contain;']),
                        Infolists\Components\TextEntry::make('ktp_path_empty')
                            ->label('KTP')
                            ->default('— Tidak ada')
                            ->hidden(fn (?ExpertProfile $record) => !empty($record->ktp_path)),
                        
                        Infolists\Components\ImageEntry::make('ijazah_path')
                            ->label('Ijazah')
                            ->hidden(fn (?ExpertProfile $record) => empty($record->ijazah_path))
                            ->height(200)
                            ->extraImgAttributes(['style' => 'object-fit: contain;']),
                        Infolists\Components\TextEntry::make('ijazah_path_empty')
                            ->label('Ijazah')
                            ->default('— Tidak ada')
                            ->hidden(fn (?ExpertProfile $record) => !empty($record->ijazah_path)),

                        Infolists\Components\ImageEntry::make('license_card_path')
                            ->label('Kartu Izin Praktik (PERADI)')
                            ->hidden(fn (?ExpertProfile $record) => empty($record->license_card_path))
                            ->height(200)
                            ->extraImgAttributes(['style' => 'object-fit: contain;']),
                        Infolists\Components\TextEntry::make('license_card_empty')
                            ->label('Kartu Izin Praktik (PERADI)')
                            ->default('— Tidak ada')
                            ->hidden(fn (?ExpertProfile $record) => !empty($record->license_card_path)),

                        Infolists\Components\ImageEntry::make('selfie_path')
                            ->label('Foto Selfie')
                            ->hidden(fn (?ExpertProfile $record) => empty($record->selfie_path))
                            ->height(200)
                            ->extraImgAttributes(['style' => 'object-fit: contain;']),
                        Infolists\Components\TextEntry::make('selfie_path_empty')
                            ->label('Foto Selfie')
                            ->default('— Tidak ada')
                            ->hidden(fn (?ExpertProfile $record) => !empty($record->selfie_path)),

                        Infolists\Components\TextEntry::make('cv_path')
                            ->label('CV / Resume')
                            ->formatStateUsing(fn (?string $state) => $state ? basename($state) : '— Tidak ada')
                            ->url(fn (?ExpertProfile $record) => $record?->cv_path ? route('expert.document.download', ['profile' => $record->id, 'type' => 'cv']) : null, true)
                            ->color('primary')
                            ->icon('heroicon-o-document-arrow-down'),
                    ])->columns(2),

                Infolists\Components\Section::make('Status Verifikasi')
                    ->schema([
                        Infolists\Components\TextEntry::make('verification_status')
                            ->label('Status')
                            ->badge()
                            ->color(fn (?string $state): string => match ($state) {
                                'pending'  => 'warning',
                                'approved' => 'success',
                                'rejected' => 'danger',
                                default    => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('rejection_reason')
                            ->label('Alasan Penolakan')
                            ->default('—'),
                        Infolists\Components\TextEntry::make('verified_at')
                            ->label('Tanggal Verifikasi')
                            ->dateTime()
                            ->default('—'),
                    ])->columns(3),
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]); // Unused since we have infolist and no create/edit routes
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.role')
                    ->label('Role')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'lawyer' => 'info',
                        'paralegal' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('verification_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'pending'  => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default    => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'pending'  => '⏳ Pending',
                        'approved' => '✅ Approved',
                        'rejected' => '❌ Rejected',
                        default    => (string) $state,
                    }),
                Tables\Columns\IconColumn::make('ktp_path')
                    ->label('KTP')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->getStateUsing(fn (ExpertProfile $record): bool => ! empty($record->ktp_path)),
                Tables\Columns\IconColumn::make('ijazah_path')
                    ->label('Ijazah')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->getStateUsing(fn (ExpertProfile $record): bool => ! empty($record->ijazah_path)),
                Tables\Columns\IconColumn::make('license_card_path')
                    ->label('PERADI')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->getStateUsing(fn (ExpertProfile $record): bool => ! empty($record->license_card_path)),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tgl Daftar')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('verification_status')
                    ->label('Status Verifikasi')
                    ->options([
                        'pending'  => '⏳ Pending',
                        'approved' => '✅ Approved',
                        'rejected' => '❌ Rejected',
                    ]),
                Tables\Filters\SelectFilter::make('user.role')
                    ->label('Role')
                    ->relationship('user', 'role')
                    ->options([
                        'lawyer'    => 'Lawyer',
                        'paralegal' => 'Paralegal',
                    ]),
            ])
            ->actions([
                // ── Download Document Actions ─────────────────
                Action::make('download_ktp')
                    ->label('KTP')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->size('sm')
                    ->url(fn (ExpertProfile $record): ?string =>
                        $record->ktp_path ? route('expert.document.download', ['profile' => $record->id, 'type' => 'ktp']) : null
                    )
                    ->visible(fn (ExpertProfile $record): bool => ! empty($record->ktp_path))
                    ->openUrlInNewTab(),

                Action::make('download_ijazah')
                    ->label('Ijazah')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->size('sm')
                    ->url(fn (ExpertProfile $record): ?string =>
                        $record->ijazah_path ? route('expert.document.download', ['profile' => $record->id, 'type' => 'ijazah']) : null
                    )
                    ->visible(fn (ExpertProfile $record): bool => ! empty($record->ijazah_path))
                    ->openUrlInNewTab(),

                // ── Approve ───────────────────────────────────
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Setujui Pendaftaran Expert')
                    ->modalDescription('Apakah Anda yakin ingin menyetujui pendaftaran expert ini? Mereka akan langsung dapat menangani kasus.')
                    ->action(function (ExpertProfile $record): void {
                        $record->update([
                            'verification_status' => 'approved',
                            'is_verified'         => true,
                            'rejection_reason'    => null,
                            'verified_at'         => now(),
                        ]);

                        // Update user's is_verified flag (forceFill since not mass-assignable)
                        $record->user->forceFill(['is_verified' => true])->save();

                        // Send email notification
                        $record->user->notify(new ExpertApprovedNotification($record));

                        FilamentNotification::make()
                            ->title('Expert disetujui')
                            ->body($record->user->name . ' telah disetujui sebagai ' . $record->user->role . '.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (ExpertProfile $record): bool => $record->verification_status !== 'approved'),

                // ── Reject ────────────────────────────────────
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Tolak Pendaftaran Expert')
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Alasan Penolakan')
                            ->required()
                            ->placeholder('Jelaskan alasan penolakan, misal: dokumen tidak jelas, data tidak valid, dll.')
                            ->maxLength(1000),
                    ])
                    ->action(function (ExpertProfile $record, array $data): void {
                        $record->update([
                            'verification_status' => 'rejected',
                            'is_verified'         => false,
                            'rejection_reason'    => $data['rejection_reason'],
                            'verified_at'         => null,
                        ]);

                        // Reset user's is_verified flag (forceFill since not mass-assignable)
                        $record->user->forceFill(['is_verified' => false])->save();

                        // Send email notification
                        $record->user->notify(new ExpertRejectedNotification($record));

                        FilamentNotification::make()
                            ->title('Expert ditolak')
                            ->body($record->user->name . ' telah ditolak. Alasan: ' . $data['rejection_reason'])
                            ->danger()
                            ->send();
                    })
                    ->visible(fn (ExpertProfile $record): bool => $record->verification_status !== 'rejected'),

                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListExpertVerifications::route('/'),
            'view'  => Pages\ViewExpertVerification::route('/{record}'),
        ];
    }
}
