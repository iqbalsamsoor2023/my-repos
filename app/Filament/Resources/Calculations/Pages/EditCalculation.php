<?php

namespace App\Filament\Resources\Calculations\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\Calculations\CalculationResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCalculation extends EditRecord
{
    protected static string $resource = CalculationResource::class;

    public function getTitle(): string
    {
        return __('Edit Calculation');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
