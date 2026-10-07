<?php

namespace App\Providers;

use App\Models\Announcement;
use App\Models\AppVersion;
use App\Models\Company;
use App\Models\EmergencyContact;
use App\Models\Event;
use App\Models\EventRsvp;
use App\Models\LogisticPartner;
use App\Models\Maintenance;
use App\Models\MaintenanceProgression;
use App\Models\Parcel;
use App\Models\Permission;
use App\Models\Pet;
use App\Models\Residence;
use App\Models\ResidenceActivationStatus;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Models\Visitor;
use App\Policies\AnnouncementPolicy;
use App\Policies\ApplicationPolicy;
use App\Policies\CompanyPolicy;
use App\Policies\DistrictDashboardPolicy;
use App\Policies\EmergencyContactPolicy;
use App\Policies\EventPolicy;
use App\Policies\EventRsvpPolicy;
use App\Policies\LogisticPartnerPolicy;
use App\Policies\MaintenancePolicy;
use App\Policies\MaintenanceProgressionPolicy;
use App\Policies\ParcelPolicy;
use App\Policies\PermissionPolicy;
use App\Policies\PetPolicy;
use App\Policies\ResidenceActivationStatusPolicy;
use App\Policies\ResidencePolicy;
use App\Policies\RolePolicy;
use App\Policies\UnitPolicy;
use App\Policies\UserPolicy;
use App\Policies\VehicleBrandPolicy;
use App\Policies\VehicleModelPolicy;
use App\Policies\VehiclePolicy;
use App\Policies\VisitorLogPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Announcement::class => AnnouncementPolicy::class,
        AppVersion::class => ApplicationPolicy::class,
        VehicleBrand::class => VehicleBrandPolicy::class,
        Company::class => CompanyPolicy::class,
        LogisticPartner::class => LogisticPartnerPolicy::class,
        EmergencyContact::class => EmergencyContactPolicy::class,
        Event::class => EventPolicy::class,
        EventRsvp::class => EventRsvpPolicy::class,
        Maintenance::class => MaintenancePolicy::class,
        MaintenanceProgression::class => MaintenanceProgressionPolicy::class,
        Parcel::class => ParcelPolicy::class,
        Permission::class => PermissionPolicy::class,
        Pet::class => PetPolicy::class,
        Residence::class => ResidencePolicy::class,
        Role::class => RolePolicy::class,
        Unit::class => UnitPolicy::class,
        User::class => UserPolicy::class,
        Vehicle::class => VehiclePolicy::class,
        VehicleModel::class => VehicleModelPolicy::class,
        Visitor::class => VisitorLogPolicy::class,
        ResidenceActivationStatus::class => ResidenceActivationStatusPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::define(DistrictDashboardPolicy::VIEW_ABILITY, [DistrictDashboardPolicy::class, 'view']);

        // Implicitly grant "Super Admin" role all permissions
        // This works in the app by using gate-related functions like auth()->user->can() and @can()
        Gate::before(function ($user, $ability) {
            return $user->hasRole('Super Admin') ? true : null;
        });
    }
}
