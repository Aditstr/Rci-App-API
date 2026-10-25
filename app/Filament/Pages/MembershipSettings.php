<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Services\MembershipConfig;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class MembershipSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Membership';

    protected static ?string $title = 'Pengaturan Membership';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.membership-settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function mount(): void
    {
        $this->form->fill(MembershipConfig::all());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('price')
                    ->label('Harga membership (Rp)')
                    ->helperText('Nominal yang dibayar client untuk satu periode membership.')
                    ->integer()
                    ->minValue(MembershipConfig::MIN_PRICE)
                    ->maxValue(MembershipConfig::MAX_PRICE)
                    ->required(),
                TextInput::make('duration_days')
                    ->label('Masa aktif (hari)')
                    ->integer()
                    ->minValue(MembershipConfig::MIN_DURATION_DAYS)
                    ->maxValue(MembershipConfig::MAX_DURATION_DAYS)
                    ->required(),
                TextInput::make('ai_daily_limit')
                    ->label('Batas pertanyaan AI per hari')
                    ->helperText('Isi 0 untuk menonaktifkan akses AI bagi member.')
                    ->integer()
                    ->minValue(MembershipConfig::MIN_AI_DAILY_LIMIT)
                    ->maxValue(MembershipConfig::MAX_AI_DAILY_LIMIT)
                    ->required(),
                Toggle::make('enabled')
                    ->label('Membership aktif dijual'),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        MembershipConfig::update($this->form->getState());

        Notification::make()
            ->title('Pengaturan membership disimpan')
            ->success()
            ->send();
    }
}
