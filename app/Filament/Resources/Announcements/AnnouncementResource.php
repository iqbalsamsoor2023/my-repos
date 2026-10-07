<?php

namespace App\Filament\Resources\Announcements;

use App\Filament\Resources\Announcements\Pages\CreateAnnouncement;
use App\Filament\Resources\Announcements\Pages\EditAnnouncement;
use App\Filament\Resources\Announcements\Pages\ListAnnouncements;
use App\Filament\Resources\Announcements\Schemas\AnnouncementForm;
use App\Filament\Resources\Announcements\Tables\AnnouncementsTable;
use App\Models\Announcement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AnnouncementResource extends Resource
{
    protected static ?string $model = Announcement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): string
    {
        return __('menu.operation_management');
    }

    public static function getModelLabel(): string
    {
        return __('app.announcement');
    }

    public static function getPluralModelLabel(): string
    {
        return __('menu.announcements');
    }

    public static function getNavigationLabel(): string
    {
        return __('menu.announcements');
    }

    public static function form(Schema $schema): Schema
    {
        return AnnouncementForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AnnouncementsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAnnouncements::route('/'),
            'create' => CreateAnnouncement::route('/create'),
            'edit' => EditAnnouncement::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $query = parent::getEloquentQuery()
            ->with(['residence:id,name,name_th']);

        if ($user->hasRole('Property Management')) {
            $query->whereHas('residence', function ($q) use ($user) {
                $q->where('property_management_user_id', $user->id);
            })->where('created_by', $user->id);
        } elseif ($user->hasRole('Property Management Operation Center')) {
            $residence_ids = get_residence_by_property_management_operation_center($user->id);

            $query->whereIn('residence_id', $residence_ids);
        } elseif ($user->hasRole('Developer')) {
            $query->where('created_by', $user->id);
        }

        return $query;
    }
}
