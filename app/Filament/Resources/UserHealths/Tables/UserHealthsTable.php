<?php

namespace App\Filament\Resources\UserHealths\Tables;

use App\Enums\User\Gender;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class UserHealthsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label(__('app.name'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('user.gender')
                    ->label(__('user.gender'))
                    ->formatStateUsing(function (string $state): string {
                        return $state == Gender::MALE->value ? __('user.' . strtolower(Gender::MALE->name)) : __('user.' . strtolower(Gender::FEMALE->name));
                    })
                    ->toggleable(),
                TextColumn::make('user.date_of_birth')
                    ->label(__('user.date_of_birth'))
                    ->date()
                    ->toggleable(),
                TextColumn::make('blood_type')
                    ->label(__('user.blood_type'))
                    ->toggleable(),
                TextColumn::make('height')
                    ->label(__('user.height'))
                    ->toggleable(),
                TextColumn::make('weight')
                    ->label(__('user.weight'))
                    ->toggleable(),
                TextColumn::make('bmi')
                    ->label(__('BMI'))
                    ->toggleable(),
                TextColumn::make('bmi_category')
                    ->label(__('user.bmi_category'))
                    ->toggleable(),
                ViewColumn::make('health_questionnaire_answers')
                    ->label(__('Health Questionnaire Answers'))
                    ->view('tables.columns.user.user-health-details')
                    ->toggleable(),
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
            ->defaultSort('created_at', 'desc');
    }
}
