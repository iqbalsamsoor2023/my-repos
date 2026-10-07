<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class DailyReportCollection extends ResourceCollection
{
    public $collects = ReportResource::class;

    /**
     * Transform the resource collection into an array.
     *
     * @param Request $request
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray($request)
    {
        $threeMonthsAgo = Carbon::now()->startOfMonth()->subMonth(3);
        $twoMonthsAgo = Carbon::now()->startOfMonth()->subMonth(2);
        $oneMonthAgo = Carbon::now()->startOfMonth()->subMonth(1);
        $currentMonth = Carbon::now()->startOfMonth();

        return [
            'data' => $this->collection,
            'meta' => [
                'available_months' => [
                    $currentMonth->format('M Y'),
                    $oneMonthAgo->format('M Y'),
                    $twoMonthsAgo->format('M Y'),
                    $threeMonthsAgo->format('M Y'),
                ],
            ],
        ];
    }
}
