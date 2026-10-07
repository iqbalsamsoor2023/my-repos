<?php

namespace App\Providers;

use App\Interfaces\AuthRepositoryInterface;
use App\Interfaces\FacilityBookingRepositoryInterface;
use App\Interfaces\LPRRepositoryInterface;
use App\Interfaces\ThaiNationalIDOCRInterface;
use App\Interfaces\UnitTenantRepositoryInterface;
use App\Interfaces\VisitorParkingCalculatorRepositoryInterface;
use App\Repositories\AuthRepository;
use App\Repositories\FacilityBookingRepository;
use App\Repositories\FacilityTimeslotRepository;
use App\Repositories\LPRRepository;
use App\Repositories\ReportRepository;
use App\Repositories\ThaiNationalIDOCRRepository;
use App\Repositories\UnitTenantRepository;
use App\Repositories\VisitorParkingCalculatorRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(FacilityTimeslotRepository::class);
        $this->app->bind(FacilityBookingRepositoryInterface::class, FacilityBookingRepository::class);
        $this->app->bind(AuthRepositoryInterface::class, AuthRepository::class);
        $this->app->bind(ThaiNationalIDOCRInterface::class, ThaiNationalIDOCRRepository::class);
        $this->app->bind(LPRRepositoryInterface::class, LPRRepository::class);
        $this->app->bind(VisitorParkingCalculatorRepositoryInterface::class, VisitorParkingCalculatorRepository::class);
        $this->app->bind(UnitTenantRepositoryInterface::class, UnitTenantRepository::class);
        $this->app->bind(ReportRepository::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
