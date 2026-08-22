<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\User;
use App\Models\WalletTransaction;

class AdminWallet extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-wallet';
    protected static ?string $navigationGroup = 'Keuangan';
    protected static ?string $title = 'Dompet Admin';
    protected static ?string $navigationLabel = 'Dompet Admin';
    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.admin-wallet';

    public $balance = 0;
    public $transactions = [];

    public function mount()
    {
        $admin = User::where('role', 'admin')->first();
        if ($admin && $admin->wallet) {
            $this->balance = $admin->wallet->balance;
            $this->transactions = WalletTransaction::where('wallet_id', $admin->wallet->id)
                                    ->orderBy('created_at', 'desc')
                                    ->limit(50)
                                    ->get();
        }
    }
}
