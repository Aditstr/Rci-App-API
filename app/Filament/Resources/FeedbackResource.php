<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FeedbackResource\Pages;
use App\Models\Feedback;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;

class FeedbackResource extends Resource
{
    protected static ?string $model = Feedback::class;
    protected static ?string $slug = 'kritik-saran';
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationGroup = 'Layanan Pengguna';
    protected static ?string $navigationLabel = 'Kritik & Saran';
    protected static ?string $modelLabel = 'Masukan';
    protected static ?string $pluralModelLabel = 'Kritik & Saran';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Masukan pengguna')->schema([
                Forms\Components\Select::make('category')->label('Kategori')
                    ->options(Feedback::CATEGORIES)->disabled()->dehydrated(false),
                Forms\Components\TextInput::make('email')->label('Email untuk dihubungi')
                    ->disabled()->dehydrated(false),
                Forms\Components\Textarea::make('message')->label('Isi masukan')
                    ->rows(7)->columnSpanFull()->disabled()->dehydrated(false),
            ])->columns(2),
            Forms\Components\Section::make('Tindak lanjut')->schema([
                Forms\Components\Select::make('status')->label('Status')
                    ->options(Feedback::STATUSES)->required()
                    ->rule(Rule::in(array_keys(Feedback::STATUSES))),
                Forms\Components\Textarea::make('admin_notes')->label('Catatan internal')
                    ->helperText('Hanya terlihat oleh admin.')->rows(4)->maxLength(5000),
            ]),
        ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Masukan pengguna')->schema([
                Infolists\Components\TextEntry::make('category')->label('Kategori')
                    ->formatStateUsing(fn (string $state): string => Feedback::CATEGORIES[$state] ?? $state),
                Infolists\Components\TextEntry::make('user.name')->label('Akun pengirim')->placeholder('Pengunjung'),
                Infolists\Components\TextEntry::make('email')->label('Email untuk dihubungi')->placeholder('Tidak diisi'),
                Infolists\Components\TextEntry::make('created_at')->label('Dikirim')->dateTime('d M Y H:i'),
                Infolists\Components\TextEntry::make('message')->label('Isi masukan')
                    ->extraAttributes(['class' => 'whitespace-pre-wrap'])->columnSpanFull(),
            ])->columns(2),
            Infolists\Components\Section::make('Tindak lanjut')->schema([
                Infolists\Components\TextEntry::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn (string $state): string => Feedback::STATUSES[$state] ?? $state),
                Infolists\Components\TextEntry::make('reviewer.name')->label('Ditangani oleh')->placeholder('Belum ditinjau'),
                Infolists\Components\TextEntry::make('reviewed_at')->label('Terakhir ditinjau')
                    ->dateTime('d M Y H:i')->placeholder('Belum ditinjau'),
                Infolists\Components\TextEntry::make('admin_notes')->label('Catatan internal')
                    ->placeholder('Belum ada catatan')->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Dikirim')->dateTime('d M Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('category')->label('Kategori')->badge()
                    ->formatStateUsing(fn (string $state): string => Feedback::CATEGORIES[$state] ?? $state),
                Tables\Columns\TextColumn::make('message')->label('Isi masukan')->limit(70)->searchable()->wrap(),
                Tables\Columns\TextColumn::make('email')->label('Email')->placeholder('Tidak diisi')->searchable(),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn (string $state): string => Feedback::STATUSES[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'baru' => 'warning',
                        'ditinjau' => 'info',
                        'selesai' => 'success',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('category')->label('Kategori')->options(Feedback::CATEGORIES),
                Tables\Filters\SelectFilter::make('status')->label('Status')->options(Feedback::STATUSES),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Lihat'),
                Tables\Actions\EditAction::make()->label('Tindak lanjuti'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFeedback::route('/'),
            'view' => Pages\ViewFeedback::route('/{record}'),
            'edit' => Pages\EditFeedback::route('/{record}/edit'),
        ];
    }
}
