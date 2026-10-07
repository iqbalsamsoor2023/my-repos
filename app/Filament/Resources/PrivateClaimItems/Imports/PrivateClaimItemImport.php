<?php

namespace App\Filament\Resources\PrivateClaimItems\Imports;


use App\Models\PrivateClaimItem;
use App\Models\PrivateClaimCategory;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PrivateClaimItemImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // Map category by name
        $category = PrivateClaimCategory::firstWhere('name', $row['category_name']);

        return new PrivateClaimItem([
            'private_claim_category_id' => $category?->id,
            'name' => $row['name'],
            'name_th' => $row['name_th'] ?? null,
        ]);
    }
}