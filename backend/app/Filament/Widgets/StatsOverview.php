<?php

namespace App\Filament\Widgets;

use App\Models\Task;
use App\Models\Trip;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            $this->makeStat('User', User::class),
            $this->makeStat('Trips', Trip::class),
            $this->makeStat('Tasks', Task::class),
        ];
    }
    protected function makeStat(string $label, string $modelClass): Stat
    {
        $thisWeek = $modelClass::where('created_at', '>=', now()->startOfWeek())->count();
        $lastWeek = $modelClass::whereBetween('created_at', [
            now()->subWeek()->startOfWeek(),
            now()->subWeek()->endOfWeek(),
        ])->count();

        // Oblicz zmianę
        $difference = $thisWeek - $lastWeek;
        $percentage = $lastWeek > 0 ? round(($difference / $lastWeek) * 100, 1) : 100;

        // Dobierz kolor i ikonę dynamicznie
        $color = $difference >= 0 ? 'success' : 'danger';
        $icon = $difference >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';

        return Stat::make($label, $modelClass::count())
            ->description(($difference >= 0 ? '+' : '') . "{$percentage}% vs last week")
            ->descriptionIcon($icon)
            ->color($color);
    }
}

