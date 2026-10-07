<?php

namespace App\Services\MediaLibrary;

use App\Models\MaintenanceProgression;
use App\Models\VisitorSetting;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\DefaultPathGenerator;

class CustomPathGenerator extends DefaultPathGenerator
{
    /*
     * Get the path for the given media, relative to the root storage path.
     */
    public function getPath(Media $media): string
    {
        if ($media->model_type == 'App\Models\Announcement') {
            if ($media->getCustomProperty('type') == 'image') {
                return config('app.path.cos')."/announcement/$media->model_id/gallery/";
            } elseif ($media->getCustomProperty('type') == 'document') {
                return config('app.path.cos')."/announcement/$media->model_id/";
            }
        } elseif ($media->model_type == 'App\Models\BillPayeeSetting') {
            return config('app.path.cos')."/bill-reminder/qr/$media->model_id/";
        } elseif ($media->model_type == 'App\Models\BlacklistedVisitor') {
            return config('app.path.cos')."/visitor_blacklist/$media->model_id/";
        } elseif ($media->model_type == 'App\Models\VehicleBrand') {
            return config('app.path.cos')."/vehicle-brand/$media->model_id/";
        } elseif ($media->model_type == 'App\Models\Comment') {
            return config('app.path.cos')."/private-claim-comment/$media->model_id/";
        } elseif ($media->model_type == 'App\Models\Company') {
            return config('app.path.cos')."/developer/$media->model_id/";
        } elseif ($media->model_type == 'App\Models\FacilityAndAmenity') {
            return config('app.path.cos')."/amenity-icon/$media->model_id/";
        } elseif ($media->model_type == 'App\Models\LogisticPartner') {
            return config('app.path.public')."/logistic-partner/$media->model_id/";
        } elseif ($media->model_type == 'App\Models\InsuranceCompany') {
            return config('app.path.cos')."/insurance-company/$media->model_id/";
        } elseif ($media->model_type == 'App\Models\EmailCampaign') {
            return config('app.path.cos')."/email-campaign/$media->model_id/";
        } elseif ($media->model_type == 'App\Models\Event') {
            return config('app.path.cos')."/event/$media->model_id/gallery/";
        } elseif ($media->model_type == 'App\Models\Invoice') {
            return config('app.path.cos')."/bill-reminder-slip/$media->model_id/";
        } elseif ($media->model_type == 'App\Models\Maintenance') {
            if ($media->getCustomProperty('type') == 'maintenance') {
                return config('app.path.cos')."/maintenance/$media->model_id/gallery/";
            } elseif ($media->getCustomProperty('type') == 'maintenance_completed') {
                return config('app.path.cos')."/maintenance/$media->model_id/completed/$media->model_id/";
            } elseif ($media->getCustomProperty('type') == 'maintenance_verification') {
                return config('app.path.cos')."/maintenance/$media->model_id/verification/$media->model_id/";
            }
        } elseif ($media->model_type == 'App\Models\MaintenanceProgression') {
            $maintenance_progression = MaintenanceProgression::whereId($media->model_id)->first();

            return config('app.path.cos')."/maintenance/$maintenance_progression->maintenance_id/gallery/";
        } elseif ($media->model_type == 'App\Models\OtherAmenity') {
            return config('app.path.cos')."/warranty-handbook/$media->model_id/";
        } elseif ($media->model_type == 'App\Models\WarrantySetting') {
            return config('app.path.cos')."/warranty-setting-handbook/$media->model_id/";
        }  elseif ($media->model_type == 'App\Models\Parcel') {
            if ($media->getCustomProperty('type') == 'signature') {
                return config('app.path.cos')."/parcel/$media->model_id/signature/";
            } else {
                return config('app.path.cos')."/parcel/$media->model_id/gallery/";
            }
        } elseif ($media->model_type == 'App\Models\Pet') {
            return config('app.path.cos')."/pet/$media->model_id/gallery/";
        } elseif ($media->model_type == 'App\Models\Residence') {
            return config('app.path.cos')."/residence/$media->model_id/";
        } elseif ($media->model_type == 'App\Models\SupportTicket') {
            if ($media->getCustomProperty('type') == 'document') {
                return config('app.path.cos')."/support-ticket/$media->model_id/";
            }
        } elseif ($media->model_type == 'App\Models\SupportTicketComment') {
            return config('app.path.cos')."/support-ticket/$media->model_id/comment/";
        } elseif ($media->model_type == 'App\Models\Transaction') {
            if ($media->getCustomProperty('type') == 'bill-reminder-slip') {
                return config('app.path.cos')."/bill-reminder-slip/$media->model_id/";
            }
        } elseif ($media->model_type == 'App\Models\Unit') {
            if ($media->getCustomProperty('attachment') == 'booking_form') {
                return config('app.path.cos')."/residence_unit/$media->model_id/booking-form/";
            } elseif ($media->getCustomProperty('attachment') == 'house_contract') {
                return config('app.path.cos')."/residence_unit/$media->model_id/residence-unit-contract/";
            } elseif ($media->getCustomProperty('attachment') == 'floor_plan_pdf') {
                return config('app.path.cos')."/residence_unit/$media->model_id/attachment/";
            } elseif ($media->getCustomProperty('attachment') == 'floor_plan') {
                return config('app.path.cos')."/residence_unit/$media->model_id/";
            }

            return config('app.path.cos')."/residence_unit/$media->model_id/";
        } elseif ($media->model_type == 'App\Models\User') {
            return config('app.path.cos')."/user/$media->model_id/";
        } elseif ($media->model_type == 'App\Models\UserTutorial') {
            if ($media->collection_name == 'website_video_tutorial') {
                return config('app.path.cos')."/user_tutorial/website/$media->model_id/";
            } elseif ($media->collection_name == 'application_video_tutorial') {
                return config('app.path.cos')."/user_tutorial/mobile_app/$media->model_id/";
            }
        } elseif ($media->model_type == 'App\Models\Vehicle') {
            return config('app.path.cos')."/vehicle/$media->model_id/";
        } elseif ($media->model_type == 'App\Models\VisitorLog') {
            if ($media->getCustomProperty('type') == 'pdpa_esign') {
                return config('app.path.cos')."/visitor/$media->model_id/pdpa-sign/";
            } elseif ($media->getCustomProperty('type') == 'vehicle_image') {
                return config('app.path.cos')."/visitor/vehicle/$media->model_id/";
            } else {
                return config('app.path.cos')."/visitor/$media->model_id/";
            }
        } elseif ($media->model_type == 'App\Models\VisitorParking') {
            if ($media->getCustomProperty('type') == 'voucher_image') {
                return config('app.path.cos')."/parking-fees/$media->model_id/";
            }
        } elseif ($media->model_type == 'App\Models\VisitorSetting') {
            if ($media->getCustomProperty('type') == 'document') {
                $visitor_setting = VisitorSetting::whereId($media->model_id)->first();

                return config('app.path.cos')."/residence/$visitor_setting->residence_id/visitor-pdpa/";
            }
        } elseif ($media->model_type == 'App\Models\CheckpointLog') {
            return config('app.path.cos')."/patrol_checkpoint/$media->model_id/";
        } elseif ($media->model_type == 'App\Models\IncidentReport') {
            return config('app.path.cos')."/house_patrol/$media->model_id/gallery/";
        } elseif ($media->model_type == 'App\Models\HouseholdItem') {
            if ($media->getCustomProperty('type') == 'living_space') {
                return config('app.path.cos')."/living_space/$media->model_id/";
            } elseif ($media->getCustomProperty('type') == 'furniture') {
                return config('app.path.cos')."/furniture/$media->model_id/";
            } elseif ($media->getCustomProperty('type') == 'home_appliance') {
                return config('app.path.cos')."/home_appliance/$media->model_id/";
            }
        } elseif ($media->model_type == 'App\Models\Bank') {
            return config('app.path.cos')."/bank/$media->model_id/";
        }

        return '-';
    }

    /*
    * Get the path for conversion, relative to the root storage path.
    */
    public function getPathForConversions(Media $media): string
    {
        if ($media->model_type == 'App\Models\VisitorLog') {
            if ($media->getCustomProperty('type') == 'pdpa_esign') {
                return config('app.path.cos')."/visitor/$media->model_id/pdpa-sign/";
            } elseif ($media->getCustomProperty('type') == 'vehicle_image') {
                return config('app.path.cos')."/visitor/vehicle/$media->model_id/";
            } else {
                return config('app.path.cos')."/visitor/$media->model_id/";
            }
        }

        return '-';
    }
}
