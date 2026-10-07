<?php

namespace App\Filament\Resources\EmergencyContacts\Tables;

use App\Models\DistrictEmergencyContact;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class EmergencyContactsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('department_type')
                    ->label(__('user.department_type'))
                    ->toggleable(),
                TextColumn::make('name')
                    ->label(__('user.emergency_contact_name'))
                    ->toggleable(),
                TextColumn::make('contact_no')
                    ->label(__('user.emergency_contact_number'))
                    ->toggleable(),
                TextColumn::make('coverage')
                    ->label(__('app.coverage'))
                    ->formatStateUsing(fn($state) => new HtmlString($state))
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label(__('app.is_active'))
                    ->boolean()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('province')
                    ->label(__('app.province'))
                    ->options(list_provinces())
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['value'],
                                fn(Builder $query): Builder => $query->whereHas('districtEmergencyContacts.thailandDistrict', function ($q) use ($data) {
                                    return $q->where('province_id', $data['value']);
                                }),
                            );
                    }),
                SelectFilter::make('department_type')
                    ->label(__('user.department_type'))
                    ->options([
                        'Hospital' => __('app.hospital'),
                        'Police' => __('app.police'),
                        'Foundation' => __('app.foundation'),
                        'Fire Station' => __('app.fire_station'),
                        'Others' => __('app.others'),
                    ]),
                SelectFilter::make('coverage_mode')
                    ->label(__('app.coverage'))
                    ->options([
                        1 => __('app.nationwide'),
                        2 => __('app.province'),
                    ])
                    ->visible(auth()->user()->hasRole(['Super Admin', 'Property Management Operation Center'])),
                Filter::make('emergency_contact_name')
                    ->schema([
                        TextInput::make('emergency_contact_name')
                            ->label(__('user.emergency_contact_name')),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (isset($data['emergency_contact_name'])) {
                            return $query->where('name', 'LIKE', '%' . $data['emergency_contact_name'] . '%');
                        }
                    }),
                Filter::make('is_active')
                    ->label(__('app.is_active'))
                    ->query(fn(Builder $query): Builder => $query->where('is_active', true)),
                Filter::make('is_deactive')
                    ->label(__('app.is_deactive'))
                    ->query(fn(Builder $query): Builder => $query->where('is_active', false)),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(3)
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    ViewAction::make()
                        ->mutateRecordDataUsing(function (array $data, Action $action): array {
                            $record = $action->getRecord();

                            $department_type = $data['department_type'];

                            if (in_array($department_type, ['Others']) == true) {
                                $data['department_type'] = 'Others';
                                $data['department_type_other'] = $record->department_type;
                            }

                            $district_emergency_contact = DistrictEmergencyContact::where('emergency_contact_id', $data['id'])->get()->pluck('thailand_district_id');
                            $data['districtEmergencyContacts']['district'] = $district_emergency_contact->toArray();
                            $data['coverage_mode'] = $record->getAttributes()['coverage_mode'];

                            return $data;
                        }),
                ]),
            ], position: RecordActionsPosition::BeforeColumns);
    }
}
