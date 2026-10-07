<?php

namespace App\Filament\Resources\InsuranceCompanies\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\InsuranceCompanies\InsuranceCompanyResource;
use Filament\Resources\Pages\ListRecords;

class ListInsuranceCompanies extends ListRecords
{
    protected static string $resource = InsuranceCompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
