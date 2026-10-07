<?php

namespace App\Filament\Resources\SupportTickets;

use App\Filament\Resources\SupportTickets\Pages\CommentSupportTicket;
use App\Filament\Resources\SupportTickets\Pages\CreateSupportTicket;
use App\Filament\Resources\SupportTickets\Pages\EditSupportTicket;
use App\Filament\Resources\SupportTickets\Pages\ListSupportTickets;
use App\Filament\Resources\SupportTickets\Pages\ViewSupportTicket;
use App\Filament\Resources\SupportTickets\Schemas\SupportTicketForm;
use App\Filament\Resources\SupportTickets\Tables\SupportTicketsTable;
use App\Models\SupportTicket;
use App\Models\Unit;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SupportTicketResource extends Resource
{
    protected static ?string $model = SupportTicket::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLifebuoy;

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): string
    {
        return __('menu.communication_management');
    }

    public static function getModelLabel(): string
    {
        return __('menu.support_ticket');
    }

    public static function getPluralModelLabel(): string
    {
        return __('menu.support_tickets');
    }

    public static function getNavigationLabel(): string
    {
        return __('menu.communicationManagement.manage_my_chat');
    }

    public static function form(Schema $schema): Schema
    {
        return SupportTicketForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SupportTicketsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSupportTickets::route('/'),
            'create' => CreateSupportTicket::route('/create'),
            'view' => ViewSupportTicket::route('/{record}'),
            'edit' => EditSupportTicket::route('/{record}/edit'),
            'comment' => CommentSupportTicket::route('/comment/{supportTicketId}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = SupportTicket::with('unit')->where('platform_identifier', 'mmb')->whereNull('deleted_at');
        $user = auth()->user();

        if ($user->hasRole('Property Management')) {

            $residence = get_residence_by_property_management($user->id);

            if ($residence) {
                $query->whereHas('unit', function ($q) use ($residence) {
                    $q->where('residence_id', $residence->id);
                });
            }
        } elseif ($user->hasRole('Property Management Operation Center')) {
            $residence_ids = get_residence_by_property_management_operation_center($user->id);

            $unit_ids = Unit::whereIn('residence_id', $residence_ids)->pluck('id');

            $query->whereIn('unit_id', $unit_ids->toArray());
        }

        return $query;
    }
}
