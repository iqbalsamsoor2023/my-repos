<?php

namespace App\Actions\Maintenance;

use App\Filament\Resources\PrivateMaintenances\PrivateMaintenanceResource;
use App\Filament\Resources\PublicMaintenances\PublicMaintenanceResource;
use App\Models\Maintenance;
use App\Models\Residence;
use App\Models\ResidenceAmenity;
use App\Models\ResidenceAmenityOption;
use App\Models\Unit;
use App\Models\UnitUser;
use App\Models\User;
use App\Support\Notifications\DashboardNotification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Notification;

abstract class BaseMaintenanceNotification
{
    protected function getMaintenanceModelData(Maintenance $maintenance): array
    {
        $models = [
            'App\\Models\\Unit' => Unit::class,
            'App\\Models\\ResidenceAmenity' => ResidenceAmenity::class,
            'App\\Models\\ResidenceAmenityOption' => ResidenceAmenityOption::class,
        ];

        if (! array_key_exists($maintenance->maintainable_type, $models)) {
            return ['model' => null, 'residenceId' => null, 'unitIds' => collect([])];
        }

        $modelClass = $models[$maintenance->maintainable_type];
        $model = $modelClass::find($maintenance->maintainable_id);

        // Determine residence ID based on maintainable type
        $residenceId = null;
        $unitIds = collect([]);

        if (in_array($maintenance->maintainable_type, ['App\\Models\\ResidenceAmenity', 'App\\Models\\Unit'])) {
            $residenceId = $model->residence_id;

            // Fetch unit IDs based on maintainable type
            if ($maintenance->maintainable_type == 'App\\Models\\Unit') {
                $unitIds = collect([$maintenance->maintainable_id]);
            } else {
                $unitIds = Unit::where('residence_id', $residenceId)->pluck('id');
            }
        } elseif ($maintenance->maintainable_type == 'App\\Models\\ResidenceAmenityOption') {
            $residenceId = $model?->residenceAmenity->residence_id;
            if ($residenceId) {
                $unitIds = Unit::where('residence_id', $residenceId)->pluck('id');
            }
        }

        return [
            'model' => $model,
            'residenceId' => $residenceId,
            'unitIds' => $unitIds,
        ];
    }

    protected function getUnitUsers(Maintenance $maintenance)
    {
        $data = $this->getMaintenanceModelData($maintenance);

        if ($data['unitIds']->isEmpty()) {
            return collect([]);
        }

        // Fetch all unit users associated with the unit ids
        return UnitUser::whereIn('unit_id', $data['unitIds'])->get();
    }

    protected function getResidenceId(Maintenance $maintenance): ?int
    {
        $data = $this->getMaintenanceModelData($maintenance);

        return $data['residenceId'];
    }

    protected function sendNotificationToUsers($unitUsers, $notificationClass, Maintenance $maintenance)
    {
        foreach ($unitUsers as $unitUser) {
            if (isset($unitUser->user)) {
                Notification::send($unitUser->user, new $notificationClass($maintenance));
            }
        }
    }

    protected function sendNotificationToPM(int $residenceId, Maintenance $maintenance, $notificationClass = null)
    {
        $residence = Residence::whereId($residenceId)->first();
        $user = User::where('id', $residence->property_management_user_id)->hasDevice()->first();

        if ($user) {
            // Send notification class if provided
            if ($notificationClass) {
                Notification::send($user, new $notificationClass($maintenance));
            }

            $this->sendDashboardNotification($user, $maintenance);
        }
    }

    /**
     * Send the PM dashboard notification for a newly created maintenance report,
     * distinguishing private (unit + category) from public (amenity/facility) claims.
     */
    protected function sendDashboardNotification(User $user, Maintenance $maintenance)
    {
        $isPublic = in_array($maintenance->maintainable_type, [
            'App\\Models\\ResidenceAmenity',
            'App\\Models\\ResidenceAmenityOption',
        ]);

        $params = [
            'residentName' => $maintenance->reportedBy?->name,
            'ticketId' => $maintenance->maintainable_claim_number,
        ];

        if ($isPublic) {
            // Public claims are tied to a facility, not a unit.
            $titleKey = 'notification.maintenance_public_created.title';
            $localizedParams = ['amenity_facility' => $this->resolveAmenityFacilityName($maintenance)];
            $detailUrl = PublicMaintenanceResource::getUrl('view', ['record' => $maintenance->id]);
            $icon = Heroicon::OutlinedBuildingOffice2;
        } else {
            $params['unit'] = $maintenance->maintainable?->unit_number;
            $titleKey = 'notification.maintenance_private_created.title';
            $localizedParams = ['category' => $this->resolvePrivateClaimCategory($maintenance)];
            $detailUrl = PrivateMaintenanceResource::getUrl('view', ['record' => $maintenance->id]);
            $icon = Heroicon::OutlinedWrenchScrewdriver;
        }

        DashboardNotification::make($titleKey)
            ->params($params)
            ->localizedParams($localizedParams)
            ->icon($icon, 'warning')
            ->viewAction($detailUrl)
            ->sendToDatabase($user);
    }

    /**
     * Resolve the private claim category (the claim item) in both English and Thai.
     * The category is read from `private_claim_snapshot`, which stores name + name_th.
     *
     * @return array{en: ?string, th: ?string}
     */
    protected function resolvePrivateClaimCategory(Maintenance $maintenance): array
    {
        $item = $maintenance->private_claim_snapshot['item'] ?? null;

        return [
            'en' => $item['name'] ?? null,
            'th' => ($item['name_th'] ?? null) ?: ($item['name'] ?? null),
        ];
    }

    /**
     * Resolve the public amenity/facility name in both English and Thai.
     *
     * @return array{en: ?string, th: ?string}
     */
    protected function resolveAmenityFacilityName(Maintenance $maintenance): array
    {
        $maintainable = $maintenance->maintainable;

        if ($maintainable instanceof ResidenceAmenityOption) {
            return [
                'en' => $maintainable->name,
                'th' => $maintainable->name_in_thai ?: $maintainable->name,
            ];
        }

        if ($maintainable instanceof ResidenceAmenity) {
            $facility = $maintainable->facilityAndAmenity;

            return [
                'en' => $facility?->name,
                'th' => $facility?->name_in_thai ?: $facility?->name,
            ];
        }

        return ['en' => null, 'th' => null];
    }
}
