<?php

namespace App\Filament\Resources\VisitorParkings\Widgets;

use App\Models\Residence;
use App\Models\VisitorParking;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Route;

class PfmsStatsOverview extends BaseWidget
{
    protected ?string $pollingInterval = '300s';

    public static function canView(): bool
    {
        if (Route::currentRouteName() === 'filament.pages.dashboard') {
            return false;
        }

        return auth()->user()->hasRole('Property Management');
    }

    protected function getStats(): array
    {
        $currUser = auth()->user();
        $oneMonthAgo = Carbon::now()->subMonth()->toDateString();

        if ($currUser->hasRole('Super Admin')) {
            $residenceIds = Residence::pluck('id')->toArray();
        } else {
            $residence = Residence::where('property_management_user_id', $currUser->id)->first();
            $residenceIds[] = $residence->id;
        }

        $visitorParkings = VisitorParking::with('visitorLog.visitingArrangements')
            ->whereHas('visitorLog.visitingArrangements', function ($query) use ($residenceIds) {
                $query->whereIn('residence_id', $residenceIds);
            })
            ->where('updated_at', '>=', $oneMonthAgo);

        $totalVisitorParkings = $visitorParkings->count();
        $today = $this->getDataToday($residenceIds);
        $thisWeek = $this->getDataThisWeek($residenceIds);
        $thisMonth = $this->getDataThisMonth($residenceIds);

        $cards[] = Stat::make('Visitor Parking Today', $today)
            ->color('success')
            ->description("from $totalVisitorParkings total Parking");

        $cards[] = Stat::make('Visitor Parking This Week', $thisWeek)
            ->color('primary')
            ->description("from $totalVisitorParkings total Parking");

        $cards[] = Stat::make('Visitor Parking This Month', $thisMonth)
            ->color('warning')
            ->description("from $totalVisitorParkings total Parking");

        return $cards;
    }

    private function getDataToday(array $residenceIds)
    {
        $count = VisitorParking::with('visitorLog.visitingArrangements')
            ->whereHas('visitorLog.visitingArrangements', function ($query) use ($residenceIds) {
                $query->whereIn('residence_id', $residenceIds);
            })
            ->where('updated_at', '>=', Carbon::now()->startOfDay())
            ->where('updated_at', '<=', Carbon::now()->endOfDay())
            ->count();

        return $count;
    }

    private function getDataThisWeek(array $residenceIds)
    {
        $count = VisitorParking::with('visitorLog.visitingArrangements')
            ->whereHas('visitorLog.visitingArrangements', function ($query) use ($residenceIds) {
                $query->whereIn('residence_id', $residenceIds);
            })
            ->where('updated_at', '>=', Carbon::now()->startOfWeek())
            ->where('updated_at', '<=', Carbon::now()->endOfWeek())
            ->count();

        return $count;
    }

    private function getDataThisMonth(array $residenceIds)
    {
        $count = VisitorParking::with('visitorLog.visitingArrangements')
            ->whereHas('visitorLog.visitingArrangements', function ($query) use ($residenceIds) {
                $query->whereIn('residence_id', $residenceIds);
            })
            ->where('updated_at', '>=', Carbon::now()->startOfMonth())
            ->where('updated_at', '<=', Carbon::now()->endOfMonth())
            ->count();

        return $count;
    }
}
