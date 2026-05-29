<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CaseResource\Pages;
use App\Filament\Resources\CaseResource\RelationManagers;
use App\Models\LegalCase;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CaseResource extends Resource
{
    protected static ?string $model = LegalCase::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationLabel = 'Kasus';

    protected static ?string $modelLabel = 'Kasus';

    protected static ?string $pluralModelLabel = 'Kasus';

    protected static ?string $slug = 'cases';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Kasus')
                    ->schema([
                        Forms\Components\TextInput::make('case_number')
                            ->label('Nomor Kasus')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Select::make('client_id')
                            ->relationship('client', 'name')
                            ->required()
                            ->searchable()
                            ->label('Klien'),
                        Forms\Components\Select::make('expert_id')
                            ->relationship('expert', 'name')
                            ->searchable()
                            ->label('Pakar (Paralegal/Lawyer)'),
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->label('Judul Kasus')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('description')
                            ->required()
                            ->label('Deskripsi Kasus')
                            ->columnSpanFull(),
                        Forms\Components\Select::make('category')
                            ->options([
                                'corporate' => 'Corporate',
                                'criminal' => 'Pidana',
                                'family' => 'Keluarga',
                                'property' => 'Properti',
                                'labor' => 'Tenaga Kerja',
                                'immigration' => 'Imigrasi',
                                'intellectual_property' => 'HAKI',
                                'tax' => 'Pajak',
                                'general' => 'Umum',
                            ])
                            ->required()
                            ->label('Kategori'),
                        Forms\Components\Select::make('priority')
                            ->options([
                                'low' => 'Rendah',
                                'medium' => 'Sedang',
                                'high' => 'Tinggi',
                                'urgent' => 'Mendesak',
                            ])
                            ->required()
                            ->label('Prioritas'),
                        Forms\Components\Select::make('status')
                            ->options([
                                'submitted' => 'Disubmit',
                                'pending' => 'Menunggu',
                                'ai_analyzing' => 'Analisis AI',
                                'assigned' => 'Ditugaskan',
                                'bidding' => 'Bidding',
                                'active' => 'Aktif',
                                'in_progress' => 'Diproses',
                                'reviewing' => 'Direview',
                                'escalated' => 'Dieskalasi',
                                'completed' => 'Selesai',
                                'cancelled' => 'Dibatalkan',
                                'dispute' => 'Sengketa',
                            ])
                            ->required()
                            ->label('Status'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('case_number')
                    ->label('No. Kasus')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Klien')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('expert.name')
                    ->label('Pakar')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category')
                    ->label('Kategori')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'submitted', 'pending', 'bidding', 'assigned' => 'gray',
                        'ai_analyzing', 'reviewing' => 'warning',
                        'active', 'in_progress' => 'primary',
                        'escalated', 'dispute', 'cancelled' => 'danger',
                        'completed' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('priority')
                    ->label('Prioritas')
                    ->badge()
                    ->colors([
                        'gray' => 'low',
                        'warning' => 'medium',
                        'danger' => ['high', 'urgent'],
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tgl Submit')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                                'submitted' => 'Disubmit',
                                'pending' => 'Menunggu',
                                'ai_analyzing' => 'Analisis AI',
                                'assigned' => 'Ditugaskan',
                                'bidding' => 'Bidding',
                                'active' => 'Aktif',
                                'in_progress' => 'Diproses',
                                'reviewing' => 'Direview',
                                'escalated' => 'Dieskalasi',
                                'completed' => 'Selesai',
                                'cancelled' => 'Dibatalkan',
                                'dispute' => 'Sengketa',
                    ]),
                Tables\Filters\SelectFilter::make('category'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCases::route('/'),
            'create' => Pages\CreateCase::route('/create'),
            'edit' => Pages\EditCase::route('/{record}/edit'),
        ];
    }
}
