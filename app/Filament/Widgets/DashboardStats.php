<?php

namespace App\Filament\Widgets;

use App\Models\LegalCase;
use App\Models\User;
use App\Models\Subscription;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStats extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Total Pengguna', User::count())
                ->description('Client, Paralegal, & Lawyer')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),
                
            Stat::make('Total Kasus', LegalCase::count())
                ->description('Semua kasus terdaftar')
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('success'),
                
            Stat::make('Kasus Aktif & Diproses', LegalCase::whereIn('status', ['active', 'in_progress', 'reviewing'])->count())
                ->description('Dalam pengerjaan pakar')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('warning'),
                
            Stat::make('Langganan Aktif', Subscription::where('status', 'active')->count())
                ->description('Client Pro & Corporate')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),
        ];
    }
}
