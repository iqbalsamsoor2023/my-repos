<?php

namespace App\Filament\Resources\VisitorParkings\Widgets;

use App\Models\Residence;
use App\Models\VisitorParking;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;

class PfmsCollectionsLineChart extends ChartWidget
{
    protected ?string $heading = 'Parking Fees Collected';

    protected int|string|array $columnSpan = 'full';

    public ?string $filter = '1 month';

    protected ?string $maxHeight = '300px';

    protected ?string $pollingInterval = '300s';

    public static function canView(): bool
    {
        if (Route::currentRouteName() === 'filament.pages.dashboard') {
            return false;
        }

        return auth()->user()->hasRole('Property Management');
    }

    protected function getFilters(): ?array
    {
        return [
            '1 week' => 'Last 1 week',
            '2 weeks' => 'Last 2 weeks',
            '1 month' => 'Last 1 month',
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $currUser = auth()->user();
        $oneMonthAgo = Carbon::now()->subMonth()->toDateString();

        if ($currUser->hasRole('Super Admin')) {
            $residenceIds = Residence::pluck('id');
        } else {
            $residence = Residence::where('property_management_user_id', $currUser->id)->first();
            $residenceIds[] = $residence->id;
        }

        $dataQuery = Trend::query(VisitorParking::with('visitorLog.visitingArrangements')
            ->whereHas('visitorLog.visitingArrangements', function ($query) use ($residenceIds) {
                $query->whereIn('residence_id', $residenceIds);
            })
            ->where('updated_at', '>=', $oneMonthAgo))
            ->between(
                start: Carbon::now()->sub($this->filter),
                end: now(),
            );

        switch ($this->filter) {
            case '1 week':
                $data = $dataQuery->perDay()->count();
                break;

            case '2 weeks':
                $data = $dataQuery->perDay()->count();
                break;

            case '1 month':
                $data = $dataQuery->perDay()->count();
                break;
        }

        $datasets[] = [
            'label' => 'Total Parking Collected',
            'data' => $data->map(fn (TrendValue $value) => $value->aggregate),
            'fill' => true,
            'backgroundColor' => 'rgb(190, 38, 40, 0.2)',
            'borderColor' => 'rgb(190, 38, 40)',
            'tension' => 0.1,
        ];

        return [
            'datasets' => $datasets,
            'labels' => $data->map(fn (TrendValue $value) => $value->date),
        ];
    }
}
