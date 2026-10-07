<?php

namespace App\Providers;

use App\Events\AmenityBookingCreated;
use App\Events\CompanyCreated;
use App\Events\MaintenanceCreated;
use App\Events\ParcelCreated;
use App\Events\PetCreated;
use App\Events\ResidenceCreated;
use App\Events\SupportTicketCreated;
use App\Events\UnitCreated;
use App\Events\UnitUserCreated;
use App\Events\VehicleCreated;
use App\Listeners\GenerateActivationSetting;
use App\Listeners\GenerateAmenityBookingRefNo;
use App\Listeners\GenerateCaseID;
use App\Listeners\GenerateCompanyEmail;
use App\Listeners\GenerateEmail;
use App\Listeners\GenerateHomeID;
use App\Listeners\GenerateInvitationCode;
use App\Listeners\GenerateMaintenanceClaimNo;
use App\Listeners\GenerateMmbID;
use App\Listeners\GenerateOtherOption;
use App\Listeners\GenerateParcelID;
use App\Listeners\GeneratePetID;
use App\Listeners\GenerateVehicleNumber;
use App\Listeners\PreventApiAuditing;
use App\Models\ActivationModule;
use App\Models\Parcel;
use App\Models\Residence;
use App\Models\ResidenceFeature;
use App\Models\Unit;
use App\Models\UnitUser;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VisitorSetting;
use App\Observers\ActivationModuleObserver;
use App\Observers\BrandObserver;
use App\Observers\MediaObserver;
use App\Observers\ParcelObserver;
use App\Observers\ResidenceFeatureObserver;
use App\Observers\ResidenceObserver;
use App\Observers\UnitObserver;
use App\Observers\UnitUserObserver;
use App\Observers\UserObserver;
use App\Observers\VehicleObserver;
use App\Observers\VisitorSettingObserver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        AmenityBookingCreated::class => [
            GenerateAmenityBookingRefNo::class,
        ],
        CompanyCreated::class => [
            GenerateCompanyEmail::class,
        ],
        MaintenanceCreated::class => [
            GenerateMaintenanceClaimNo::class,
        ],
        ParcelCreated::class => [
            GenerateParcelID::class,
        ],
        PetCreated::class => [
            GeneratePetID::class,
        ],
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        ResidenceCreated::class => [
            // GenerateEmail::class,
            GenerateOtherOption::class,
            GenerateActivationSetting::class,
        ],
        SupportTicketCreated::class => [
            GenerateCaseID::class,
        ],
        UnitCreated::class => [
            GenerateInvitationCode::class,
            GenerateHomeID::class,
        ],
        UnitUserCreated::class => [
            GenerateMmbID::class,
        ],
        VehicleCreated::class => [
            GenerateVehicleNumber::class,
        ],
        \OwenIt\Auditing\Events\Auditing::class => [
            PreventApiAuditing::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        Parcel::observe(ParcelObserver::class);
        VehicleBrand::observe(BrandObserver::class);
        Vehicle::observe(VehicleObserver::class);
        Residence::observe(ResidenceObserver::class);
        ActivationModule::observe(ActivationModuleObserver::class);
        ResidenceFeature::observe(ResidenceFeatureObserver::class);
        Unit::observe(UnitObserver::class);
        UnitUser::observe(UnitUserObserver::class);
        User::observe(UserObserver::class);
        VisitorSetting::observe(VisitorSettingObserver::class);
        $this->bootMediaObserver();
    }

    protected function bootMediaObserver()
    {
        $mediaClass = config('media-library.media_model');
        app('events')->forget('eloquent.deleted: '.$mediaClass);

        $mediaClass::observe(new MediaObserver);
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     *
     * @return bool
     */
    public function shouldDiscoverEvents()
    {
        return false;
    }
}
