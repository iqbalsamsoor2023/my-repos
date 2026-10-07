<?php

namespace App\Filament\Resources\IncidentReports\Widgets;

use App\Models\IncidentReport;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class IrsStatsOverview extends BaseWidget
{
    protected ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = 'half';

    public static function canView(): bool
    {
        if (Route::currentRouteName() === 'filament.pages.dashboard') {
            return false;
        }

        return auth()->user()->hasRole('Property Management');
    }

    protected function getCards(): array
    {
        $user = auth()->user();
        $oneMonthAgo = Carbon::now()->subMonths(1)->toDateString();
        $incidentReports = IncidentReport::where('created_at', '>=', $oneMonthAgo);

        if ($user->hasRole('Super Admin')) {
            $incidentReports = $incidentReports;
        } elseif ($user->hasRole('Property Management')) {
            $incidentReports->where('mmb_residence_id', $user->propertyManagement->id);
        } elseif ($user->hasRole('Property Management Operation Center')) {
            $residenceIds = get_residence_by_property_management_operation_center($user->id);

            $incidentReports->whereIn('mmb_residence_id', $residenceIds);
        }

        $total = $incidentReports->count();
        $totalReport = $incidentReports->clone()
            ->where('created_at', '>=', Carbon::now()->startOfDay())
            ->where('created_at', '<=', Carbon::now()->endOfDay())
            ->count();
        $highCase = $incidentReports->clone()
            ->select('title as incident_title', DB::raw('count(title) as total_irs'))
            ->groupBy('title')
            ->orderBy('total_irs', 'desc')
            ->limit(1);

        $otherCases = $incidentReports->clone()->where('title', '!=', $highCase->value('incident_title'))->count();

        $statuses[] = [
            'value' => $totalReport,
            'label' => "Today's Report",
        ];

        $statuses[] = [
            'value' => is_null($highCase->value('total_irs')) == false ? $highCase->value('total_irs') : 0,
            'label' => 'Total High Case ('.$highCase->value('incident_title').')',
        ];

        $statuses[] = [
            'value' => $otherCases,
            'label' => 'Total Other Cases',
        ];

        foreach ($statuses as $key => $value) {
            switch ($key) {
                case 0:
                    $color = 'primary'; // Today's Report
                    break;

                case 1:
                    $color = 'danger'; // Total High Cases
                    break;

                case 2:
                    $color = 'warning'; // Total Other Cases
                    break;

                default:
                    $color = '';
                    break;
            }

            $cards[] = Stat::make($value['label'], $value['value'])
                ->color($color)
                ->description("from $total total");
        }

        return $cards;
    }
}
