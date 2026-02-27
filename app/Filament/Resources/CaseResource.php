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
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
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
