<?php

namespace App\Filament\Resources\Profiles;

use Filament\Schemas\Schema;
use App\Filament\Resources\Profiles\Pages\EditProfile;
use App\Filament\Resources\Profiles\Pages\ListProfiles;
use App\Filament\Resources\Profiles\Schemas\ProfileForm;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;

class ProfileResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Schema $schema): Schema
    {
        return ProfileForm::configure($schema);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProfiles::route('/'),
            'edit' => EditProfile::route('/{record}/edit'),
        ];
    }
}
