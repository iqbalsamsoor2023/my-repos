<?php

namespace App\Filament\Resources\InsuranceCompanies\Tables;

use App\Enums\Company\InsuranceTypeEnum;
use App\Models\InsuranceCompany;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class InsuranceCompaniesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('logo')
                    ->state(
                        fn($record) =>
                        optional(
                            $record->getMedia('insurance_company_images')
                                ->where('custom_properties.type', 'Insurance')
                                ->first()
                        )->getUrl()
                            ?? 'https://dashboard.mymooban.co.th/images/no-image.png'
                    )
                    ->disk('cos'),
                TextColumn::make('type')
                    ->label(__('app.type'))
                    ->badge()
                    ->formatStateUsing(fn($state) => InsuranceTypeEnum::from($state)->label())
                    ->toggleable(),
                TextColumn::make('name')
                    ->label(__('app.company_name_en'))
                    ->copyable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('name_th')
                    ->label(__('app.company_name_th'))
                    ->copyable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('website_url')
                    ->label(__('app.website_url'))
                    ->copyable()
                    ->searchable()
                    ->toggleable(),
                SpatieMediaLibraryImageColumn::make('image')
                    ->label(__('app.image'))
                    ->collection('default')
                    ->toggleable(),
                ToggleColumn::make('is_active')
                    ->label(__('app.is_active')),
                TextColumn::make('created_at')
                    ->label(__('app.created_at_date'))
                    ->getStateUsing(function (Model $record) {
                        return $record->created_at->format('d-M-y');
                    }),
                TextColumn::make('created_at_time')
                    ->label(__('app.created_at_time'))
                    ->getStateUsing(function (Model $record) {
                        return $record->created_at->format('H:i:s');
                    }),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->label(__('app.type'))
                    ->options(collect(InsuranceTypeEnum::cases())
                        ->mapWithKeys(fn($case) => [$case->value => __($case->label())])
                        ->toArray()),
                SelectFilter::make('name')
                    ->label(__('app.company_name'))
                    ->options(function () {
                        return InsuranceCompany::query()
                            ->orderBy('name')
                            ->get()
                            ->mapWithKeys(function ($company) {
                                return [$company->name => "{$company->name} ({$company->name_th})"];
                            })
                            ->toArray();
                    })
                    ->searchable(),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('created_from')
                            ->label(__('app.created_from')),
                        DatePicker::make('created_until')
                            ->label(__('app.created_until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
                Filter::make('is_active')
                    ->label(__('app.is_active'))
                    ->query(fn(Builder $query): Builder => $query->where('is_active', true))
                    ->toggle(),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(3)
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    ViewAction::make(),
                    DeleteAction::make(),
                ]),
            ], position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
