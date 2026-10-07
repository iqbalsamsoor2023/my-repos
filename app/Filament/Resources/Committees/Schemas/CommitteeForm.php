<?php

namespace App\Filament\Resources\Committees\Schemas;

use App\Enums\Residence\CommitteeRole;
use App\Models\Unit;
use App\Models\UnitUser;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class CommitteeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('committee.committee'))
                    ->description(__('committee.committee_detail'))
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('residence_id')
                            ->label(__('app.mooban_or_residence'))
                            ->options(fn() => list_residences())
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set): void {
                                $set('unit_id', null);
                                $set('user_ids', []);
                            })
                            ->disabled(fn(string $operation) => $operation === 'edit')
                            ->dehydrated(fn(string $operation) => $operation !== 'edit'),
                        Select::make('unit_id')
                            ->label(__('unit.unit_number'))
                            ->options(function (Get $get): array {
                                $residenceId = $get('residence_id');

                                if (! $residenceId) {
                                    return [];
                                }

                                $query = Unit::query()->where('residence_id', $residenceId);

                                $user = auth()->user();
                                if ($user?->hasRole('Property Management') && ! $user->hasRole('Super Admin')) {
                                    $query->whereHas('residence', fn ($residenceQuery) => $residenceQuery->where('property_management_user_id', $user->id));
                                }

                                return $query
                                    ->orderBy('unit_number')
                                    ->pluck('unit_number', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set): void {
                                $set('user_ids', []);
                            })
                            ->disabled(fn(string $operation) => $operation === 'edit')
                            ->dehydrated(fn(string $operation) => $operation !== 'edit'),
                        Select::make('role')
                            ->label(__('committee.committee_position'))
                            ->options(CommitteeRole::class)
                            ->required()
                            ->live()
                            ->native(false)
                            ->afterStateUpdated(function (Get $get, Set $set): void {
                                $role = self::resolveRole($get('role'));

                                if ($role === CommitteeRole::MEMBER) {
                                    return;
                                }

                                $userIds = collect($get('user_ids') ?? [])
                                    ->filter(fn ($id) => filled($id))
                                    ->values()
                                    ->all();

                                if (count($userIds) > 1) {
                                    $set('user_ids', [(string) $userIds[0]]);
                                }
                            })
                            ->disabled(fn(string $operation) => $operation === 'edit'),
                        Select::make('user_ids')
                            ->label(__('app.resident'))
                            ->multiple()
                            ->options(function (Get $get): array {
                                $unitId = $get('unit_id');
                                $residenceId = $get('residence_id');

                                if (! $unitId || ! $residenceId) {
                                    return [];
                                }

                                $query = UnitUser::query()
                                    ->join('users', 'users.id', '=', 'unit_user.user_id')
                                    ->join('units', 'units.id', '=', 'unit_user.unit_id')
                                    ->where('units.id', $unitId)
                                    ->where('units.residence_id', $residenceId)
                                    ->distinct()
                                    ->orderBy('users.name');

                                $user = auth()->user();
                                if ($user?->hasRole('Property Management') && ! $user->hasRole('Super Admin')) {
                                    $query
                                        ->join('residences', 'residences.id', '=', 'units.residence_id')
                                        ->where('residences.property_management_user_id', $user->id);
                                }

                                return $query->pluck('users.name', 'users.id')->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->helperText(function (Get $get): string {
                                $role = self::resolveRole($get('role'));

                                return $role === CommitteeRole::MEMBER
                                    ? __('committee.member_multi_select_hint')
                                    : __('committee.single_seat_hint');
                            })
                            ->required()
                            ->disabled(fn(Get $get, string $operation) => $operation === 'edit' || blank($get('role')))
                            ->dehydrated(fn(string $operation) => $operation !== 'edit'),

                        Fieldset::make(__('committee.term_start'))
                            ->columnSpan(1)
                            ->columns(2)
                            ->schema([
                                Select::make('term_start_month')
                                    ->label(__('committee.month'))
                                    ->options(static::getMonthOptions())
                                    ->required()
                                    ->default(now()->month)
                                    ->native(false)
                                    ->selectablePlaceholder(false),

                                Select::make('term_start_year')
                                    ->label(__('committee.year'))
                                    ->options(static::getYearOptions())
                                    ->required()
                                    ->default(now()->year)
                                    ->native(false)
                                    ->selectablePlaceholder(false),
                            ]),

                        Fieldset::make(__('committee.term_end'))
                            ->columnSpan(1)
                            ->columns(2)
                            ->schema([
                                Select::make('term_end_month')
                                    ->label(__('committee.month'))
                                    ->options(static::getMonthOptions())
                                    ->native(false)
                                    ->placeholder(__('committee.select_month'))
                                    ->helperText(__('committee.leave_empty_ongoing')),

                                Select::make('term_end_year')
                                    ->label(__('committee.year'))
                                    ->options(static::getYearOptions())
                                    ->native(false)
                                    ->placeholder(__('committee.select_year'))
                                    ->helperText(__('committee.leave_empty_ongoing')),
                            ]),
                    ])
            ]);
    }

    /**
     * Get month options with translations.
     */
    protected static function getMonthOptions(): array
    {
        return [
            1 => __('committee.months.january'),
            2 => __('committee.months.february'),
            3 => __('committee.months.march'),
            4 => __('committee.months.april'),
            5 => __('committee.months.may'),
            6 => __('committee.months.june'),
            7 => __('committee.months.july'),
            8 => __('committee.months.august'),
            9 => __('committee.months.september'),
            10 => __('committee.months.october'),
            11 => __('committee.months.november'),
            12 => __('committee.months.december'),
        ];
    }

    /**
     * Get year options range.
     */
    protected static function getYearOptions(): array
    {
        $currentYear = now()->year;
        $years = [];

        for ($year = $currentYear - 10; $year <= $currentYear + 10; $year++) {
            $years[$year] = (string) $year;
        }

        return $years;
    }

    protected static function resolveRole(mixed $roleValue): ?CommitteeRole
    {
        if ($roleValue instanceof CommitteeRole) {
            return $roleValue;
        }

        if ($roleValue === null || $roleValue === '') {
            return null;
        }

        if (is_int($roleValue) || (is_string($roleValue) && ctype_digit($roleValue))) {
            return CommitteeRole::tryFrom((int) $roleValue);
        }

        return null;
    }
}
