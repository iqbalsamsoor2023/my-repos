<?php

namespace App\Filament\Resources\SupportTickets\Pages;

use App\Filament\Resources\SupportTickets\SupportTicketResource;
use App\Filament\Resources\SupportTickets\Widgets\SupportTicketCategoryChart;
use App\Filament\Resources\SupportTickets\Widgets\SupportTicketCategoryItemChart;
use App\Filament\Resources\SupportTickets\Widgets\SupportTicketStatusOverview;
use App\Filament\Resources\SupportTickets\Widgets\SupportTicketTrendChart;
use Filament\Actions\CreateAction;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;

class ListSupportTickets extends ListRecords
{
    // Feeds the table's filter and search state to the widgets, which read it through
    // InteractsWithPageTable. Without it their reactive properties are handed null.
    use ExposesTableToWidgets;

    protected static string $resource = SupportTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('menu.new_support_ticket')),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            SupportTicketStatusOverview::class,
            SupportTicketCategoryChart::class,
            SupportTicketTrendChart::class,
            SupportTicketCategoryItemChart::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 3;
    }
}
