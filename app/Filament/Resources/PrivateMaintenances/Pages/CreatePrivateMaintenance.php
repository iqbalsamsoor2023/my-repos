<?php

namespace App\Filament\Resources\PrivateMaintenances\Pages;

use App\Actions\Maintenance\SendMaintenanceCreateNotification;
use App\Filament\Resources\PrivateMaintenances\PrivateMaintenanceResource;
use App\Models\Amenity;
use App\Models\PrivateClaimItem;
use App\Models\PrivateClaimItemTitle;
use Filament\Resources\Pages\CreateRecord;

class CreatePrivateMaintenance extends CreateRecord
{
    protected static string $resource = PrivateMaintenanceResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function getTitle(): string
    {
        return __('menu.create_private_maintenance');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // if ($data['amenity'] != 'Others') {
        //     $amenity = Amenity::whereId($data['amenity'])->first();
        // } else {
        //     $amenity = Amenity::where('amenity_name', 'Others')->first();
        //     $data['miscellaneous'] = $data['amenity_name'];
        //     // $amenity = Amenity::where('amenity_name', 'LIKE', '%'.$data['amenity_name'].'%')->where('residence_id', $data['residence_id'])->first();

        //     // if (! $amenity) {
        //     //     $amenity = new Amenity();
        //     //     $amenity->residence_id = $data['residence_id'];
        //     //     $amenity->amenity_name = $data['amenity_name'];
        //     //     $amenity->save();
        //     // }
        // }

        // $data['claimable_item_details'] = [
        //     'id' => $amenity->id,
        //     'amenity_name' => $amenity->amenity_name,
        //     'warranty_period' => $amenity->warranty_period,
        //     'period_type' => $amenity->period_type,
        //     'supplier' => $amenity->supplier,
        //     'is_out_warranty' => $amenity->is_out_warranty,
        //     'remark' => $amenity->remark,
        // ];

        $item     = PrivateClaimItem::find($data['private_claim_item_id'] ?? null);
        $title    = PrivateClaimItemTitle::find($data['private_claim_item_title_id'] ?? null);

        $data['private_claim_snapshot'] = [
            'item' => $item ? [
                'id' => $item->id,
                'name' => $item->name,
                'name_th' => $item->name_th,
            ] : null,

            'title' => $title ? [
                'id' => $title->id,
                'name' => $title->name,
                'name_th' => $title->name_th,
            ] : null,
        ];

        $data['maintainable_type'] = 'App\Models\Unit';
        $data['reported_by'] = Auth()->user()->id;

        return $data;
    }

    protected function afterCreate(): void
    {
        $sendMaintenanceCreateNotification = new SendMaintenanceCreateNotification;
        $sendMaintenanceCreateNotification->execute($this->record);
    }
}
