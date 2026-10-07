<?php

namespace App\Console\Commands;

use App\Console\Commands\DataMigrations\AmenitiesData;
use App\Console\Commands\DataMigrations\AnnouncementsData;
use App\Console\Commands\DataMigrations\ApplicationVersionsData;
use App\Console\Commands\DataMigrations\AutoSendReportsData;
use App\Console\Commands\DataMigrations\BillPayeeBankDetailsData;
use App\Console\Commands\DataMigrations\BillPayeeSettingsData;
use App\Console\Commands\DataMigrations\BlacklistedVisitorsData;
use App\Console\Commands\DataMigrations\BrandsData;
use App\Console\Commands\DataMigrations\ClaimableItemsData;
use App\Console\Commands\DataMigrations\CommentsData;
use App\Console\Commands\DataMigrations\CourierCompaniesData;
use App\Console\Commands\DataMigrations\DevelopersData;
use App\Console\Commands\DataMigrations\EmergencyContactsData;
use App\Console\Commands\DataMigrations\EventsData;
use App\Console\Commands\DataMigrations\FacilitiesData;
use App\Console\Commands\DataMigrations\FacilityTimeslotsData;
use App\Console\Commands\DataMigrations\InvoicesData;
use App\Console\Commands\DataMigrations\MaintenancesData;
use App\Console\Commands\DataMigrations\ModelHistoriesData;
use App\Console\Commands\DataMigrations\ModulesActivationData;
use App\Console\Commands\DataMigrations\OtherAmenitiesData;
use App\Console\Commands\DataMigrations\ParcelsData;
use App\Console\Commands\DataMigrations\ParkingsData;
use App\Console\Commands\DataMigrations\PetsData;
use App\Console\Commands\dataMigrations\PMOCUsersData;
use App\Console\Commands\DataMigrations\PrebookVisitorData;
use App\Console\Commands\DataMigrations\PropertyManagementsData;
use App\Console\Commands\DataMigrations\Register3rdPartyVisitorCardData;
use App\Console\Commands\DataMigrations\ResidencesData;
use App\Console\Commands\DataMigrations\SosManagementsData;
use App\Console\Commands\DataMigrations\SubDistrictsData;
use App\Console\Commands\DataMigrations\SuperAdminUsersData;
use App\Console\Commands\DataMigrations\UnitsData;
use App\Console\Commands\DataMigrations\UserHealthsData;
use App\Console\Commands\DataMigrations\UsersData;
use App\Console\Commands\DataMigrations\UserTutorialsData;
use App\Console\Commands\DataMigrations\VehicleInsuranceCompaniesData;
use App\Console\Commands\DataMigrations\VehicleModelsData;
use App\Console\Commands\DataMigrations\VehiclesData;
use App\Console\Commands\DataMigrations\VisitorCardsData;
use App\Console\Commands\DataMigrations\VisitorParkingsData;
use App\Console\Commands\DataMigrations\VisitorPDPAsData;
use App\Console\Commands\DataMigrations\VisitorPurposesData;
use App\Console\Commands\DataMigrations\VisitorRemarksData;
use App\Console\Commands\DataMigrations\VisitorsData;
use App\Console\Commands\ImageMigrations\AnnouncementsImage;
use App\Console\Commands\ImageMigrations\BillPayeeSettingsImage;
use App\Console\Commands\ImageMigrations\BillSlipsImage;
use App\Console\Commands\ImageMigrations\BlacklistedVisitorsImage;
use App\Console\Commands\ImageMigrations\DevelopersImage;
use App\Console\Commands\ImageMigrations\EventsImage;
use App\Console\Commands\ImageMigrations\MaintenancesImage;
use App\Console\Commands\ImageMigrations\ParcelsImage;
use App\Console\Commands\ImageMigrations\PetsImage;
use App\Console\Commands\ImageMigrations\ResidencesImage;
use App\Console\Commands\ImageMigrations\UnitsImage;
use App\Console\Commands\ImageMigrations\UsersImage;
use App\Console\Commands\ImageMigrations\VehiclesImage;
use App\Console\Commands\ImageMigrations\VisitorsImage;
use App\Console\Commands\ImageMigrations\VouchersImage;
use App\Console\Commands\ImageMigrations\WarrantyHandbooksImage;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Support\Benchmark;
use Illuminate\Support\Facades\Artisan;

class DataMigration extends Command implements Isolatable
{
    protected $usersData;

    protected $developersData;

    protected $residencesData;

    protected $unitsData;

    protected $userHealthsData;

    protected $applicationVersionsData;

    protected $emergencyContactsData;

    protected $userTutorialsData;

    protected $announcementsData;

    protected $parcelsData;

    protected $autoSendReportsData;

    protected $eventsData;

    protected $modulesActivationData;

    protected $amenitiesData;

    protected $maintenancesData;

    protected $facilitiesData;

    protected $facilityTimeslotsData;

    protected $sosManagementsData;

    protected $billPayeeSettingsData;

    protected $invoicesData;

    protected $billPayeeBankDetailsData;

    protected $otherAmenitiesData;

    protected $commentsData;

    protected $visitorPurposesData;

    protected $visitorCardsData;

    protected $visitorRemarksData;

    protected $visitorsData;

    protected $blacklistedVisitorsData;

    protected $propertyManagementsData;

    protected $vehicleInsuranceCompaniesData;

    protected $parkingsData;

    protected $vehiclesData;

    protected $petsData;

    protected $superAdminUsersData;

    protected $vehicleModelsData;

    protected $modelHistoriesData;

    protected $developersImage;

    protected $residencesImage;

    protected $usersImage;

    protected $unitsImage;

    protected $announcementsImage;

    protected $parcelsImage;

    protected $eventsImage;

    protected $maintenancesImage;

    protected $billPayeeSettingsImage;

    protected $billSlipsImage;

    protected $warrantyHandbooksImage;

    protected $blacklistedVisitorsImage;

    protected $visitorsImage;

    protected $vehiclesImage;

    protected $petsImage;

    protected $subDistrictsData;

    protected $claimableItemsData;

    protected $PMOCUsersData;

    protected $visitorPDPAsData;

    protected $visitorParkingsData;

    protected $brandsData;

    protected $courierCompaniesData;

    protected $register3rdPartyVisitorCardData;

    protected $vouchersImage;

    protected $prebookVisitorData;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data-migration:run
    {--residence_id= : Only trigger for a particulat residence ("example: 2219)}'; // --residence_id=2219

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run data migration from MMB to MMB2.';

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(UsersData $usersData,
        DevelopersData $developersData,
        ResidencesData $residenceData,
        UnitsData $unitsData,
        UserHealthsData $userHealthsData,
        ApplicationVersionsData $applicationVersionsData,
        EmergencyContactsData $emergencyContactsData,
        UserTutorialsData $userTutorialsData,
        AnnouncementsData $announcementsData,
        ParcelsData $parcelsData,
        AutoSendReportsData $autoSendReportsData,
        EventsData $eventsData,
        ModulesActivationData $modulesActivationData,
        AmenitiesData $amenitiesData,
        MaintenancesData $maintenancesData,
        FacilitiesData $facilitiesData,
        FacilityTimeslotsData $facilityTimeslotsData,
        SosManagementsData $sosManagementsData,
        BillPayeeSettingsData $billPayeeSettingsData,
        InvoicesData $invoicesData,
        BillPayeeBankDetailsData $billPayeeBankDetailsData,
        OtherAmenitiesData $otherAmenitiesData,
        CommentsData $commentsData,
        VisitorPurposesData $visitorPurposesData,
        VisitorCardsData $visitorCardsData,
        VisitorRemarksData $visitorRemarksData,
        VisitorsData $visitorsData,
        BlacklistedVisitorsData $blacklistedVisitorsData,
        PropertyManagementsData $propertyManagementsData,
        VehicleInsuranceCompaniesData $vehicleInsuranceCompaniesData,
        ParkingsData $parkingsData,
        VehiclesData $vehiclesData,
        PetsData $petsData,
        SuperAdminUsersData $superAdminUsersData,
        VehicleModelsData $vehicleModelsData,
        ModelHistoriesData $modelHistoriesData,
        DevelopersImage $developersImage,
        ResidencesImage $residencesImage,
        UsersImage $usersImage,
        UnitsImage $unitsImage,
        AnnouncementsImage $announcementsImage,
        ParcelsImage $parcelsImage,
        EventsImage $eventsImage,
        MaintenancesImage $maintenancesImage,
        BillPayeeSettingsImage $billPayeeSettingsImage,
        BillSlipsImage $billSlipsImage,
        WarrantyHandbooksImage $warrantyHandbooksImage,
        BlacklistedVisitorsImage $blacklistedVisitorsImage,
        VisitorsImage $visitorsImage,
        VehiclesImage $vehiclesImage,
        PetsImage $petsImage,
        SubDistrictsData $subDistrictsData,
        ClaimableItemsData $claimableItemsData,
        PMOCUsersData $PMOCUsersData,
        VisitorPDPAsData $visitorPDPAsData,
        VisitorParkingsData $visitorParkingsData,
        BrandsData $brandsData,
        CourierCompaniesData $courierCompaniesData,
        Register3rdPartyVisitorCardData $register3rdPartyVisitorCardData,
        PrebookVisitorData $prebookVisitorData,
        VouchersImage $vouchersImage)
    {
        $this->usersData = $usersData;
        $this->developersData = $developersData;
        $this->residencesData = $residenceData;
        $this->unitsData = $unitsData;
        $this->userHealthsData = $userHealthsData;
        $this->applicationVersionsData = $applicationVersionsData;
        $this->emergencyContactsData = $emergencyContactsData;
        $this->userTutorialsData = $userTutorialsData;
        $this->announcementsData = $announcementsData;
        $this->parcelsData = $parcelsData;
        $this->autoSendReportsData = $autoSendReportsData;
        $this->eventsData = $eventsData;
        $this->modulesActivationData = $modulesActivationData;
        $this->amenitiesData = $amenitiesData;
        $this->maintenancesData = $maintenancesData;
        $this->facilitiesData = $facilitiesData;
        $this->facilityTimeslotsData = $facilityTimeslotsData;
        $this->sosManagementsData = $sosManagementsData;
        $this->billPayeeSettingsData = $billPayeeSettingsData;
        $this->invoicesData = $invoicesData;
        $this->billPayeeBankDetailsData = $billPayeeBankDetailsData;
        $this->otherAmenitiesData = $otherAmenitiesData;
        $this->commentsData = $commentsData;
        $this->visitorPurposesData = $visitorPurposesData;
        $this->visitorCardsData = $visitorCardsData;
        $this->visitorRemarksData = $visitorRemarksData;
        $this->visitorsData = $visitorsData;
        $this->blacklistedVisitorsData = $blacklistedVisitorsData;
        $this->propertyManagementsData = $propertyManagementsData;
        $this->vehicleInsuranceCompaniesData = $vehicleInsuranceCompaniesData;
        $this->parkingsData = $parkingsData;
        $this->vehiclesData = $vehiclesData;
        $this->petsData = $petsData;
        $this->superAdminUsersData = $superAdminUsersData;
        $this->vehicleModelsData = $vehicleModelsData;
        $this->modelHistoriesData = $modelHistoriesData;
        $this->developersImage = $developersImage;
        $this->residencesImage = $residencesImage;
        $this->usersImage = $usersImage;
        $this->unitsImage = $unitsImage;
        $this->announcementsImage = $announcementsImage;
        $this->parcelsImage = $parcelsImage;
        $this->eventsImage = $eventsImage;
        $this->maintenancesImage = $maintenancesImage;
        $this->billPayeeSettingsImage = $billPayeeSettingsImage;
        $this->billSlipsImage = $billSlipsImage;
        $this->warrantyHandbooksImage = $warrantyHandbooksImage;
        $this->blacklistedVisitorsImage = $blacklistedVisitorsImage;
        $this->visitorsImage = $visitorsImage;
        $this->vehiclesImage = $vehiclesImage;
        $this->petsImage = $petsImage;
        $this->subDistrictsData = $subDistrictsData;
        $this->claimableItemsData = $claimableItemsData;
        $this->PMOCUsersData = $PMOCUsersData;
        $this->visitorPDPAsData = $visitorPDPAsData;
        $this->visitorParkingsData = $visitorParkingsData;
        $this->brandsData = $brandsData;
        $this->courierCompaniesData = $courierCompaniesData;
        $this->vouchersImage = $vouchersImage;
        $this->register3rdPartyVisitorCardData = $register3rdPartyVisitorCardData;
        $this->prebookVisitorData = $prebookVisitorData;

        parent::__construct($this);
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $start = microtime(true);
        $residence_id = $this->option('residence_id');

        $migrations = [
            // 'Artisan Seed Roles' => 'db:seed --class=RoleSeeder', //once
            // 'Artisan Seed Thailand Address' => 'db:seed --class=ThailandAddressSeeder', //once
            // 'Migrate subdistrict table' => $this->subDistrictsData, //once //complete compared to package
            // 'Artisan Seed Countries' => 'db:seed --class=CountrySeeder', //once
            // 'Artisan Seed Health Questionnaire Seeder' => 'db:seed --class=HealthQuestionnaireSeeder', //once
            // 'Artisan Seed Health Questionnaire Answer Seeder' => 'db:seed --class=HealthQuestionnaireAnswerSeeder', //once
            // 'Migrate brand table' => $this->brandsData, //pass //once
            // 'Migrate courier table' => $this->courierCompaniesData, //pa/private/var/folders/23/5z_qd1ls31scmyp253gtzwwm0000gn/T/AppTranslocation/24D1C581-3615-4D9D-A059-EC01F569DB35/d/Visual Studio Code.app/Contents/Resources/app/out/vs/code/electron-sandbox/workbench/workbench.htmlss //once
            // 'Artisan Seed Banks' => 'db:seed --class=BankSeeder', //once
            // 'Artisan Seed Payments' => 'db:seed --class=PaymentSeeder', //once
            // 'Migrate vehicle insurance table' => $this->vehicleInsuranceCompaniesData, //once
            // 'Migrate vehicle model table' => $this->vehicleModelsData,  //once
            // 'Migrate developer table' => $this->developersData, //pass //once
            // 'Migrate developer image' => $this->developersImage, //pass //once
            // 'Migrate user tutorial table' => $this->userTutorialsData, //once
            // 'Migrate residence table' => $this->residencesData, //once
            // 'Migrate residence image' => $this->residencesImage, //once
            // 'Migrate super admin users table' => $this->superAdminUsersData, //once
            // 'Artisan Seed Token' => 'db:seed --class=TokenSeeder', //once

            'Migrate users table' => $this->usersData,
            'Migrate users image' => $this->usersImage,

            // 'Migrate pmoc users table' => $this->PMOCUsersData, //once

            'Migrate units table' => $this->unitsData, // unituser
            'Migrate units image' => $this->unitsImage, // unituser
            'Migrate user health table' => $this->userHealthsData, // pass
            'Migrate visitor pdpa table' => $this->visitorPDPAsData, // pass

            // 'Migrate emergency contacts table' => $this->emergencyContactsData, //once

            'Migrate announcements table' => $this->announcementsData,
            'Migrate announcements image' => $this->announcementsImage,
            'Migrate parcels table' => $this->parcelsData,
            'Migrate parcels image' => $this->parcelsImage,
            'Migrate auto send reports table' => $this->autoSendReportsData,
            'Migrate events table' => $this->eventsData, // event_rsvps
            'Migrate events image' => $this->eventsImage, // event_rsvps
            'Migrate modules activation table' => $this->modulesActivationData,

            'Migrate facilities table' => $this->facilitiesData,
            'Migrate claimable item table' => $this->claimableItemsData,
            'Migrate amenities table' => $this->amenitiesData,
            'Migrate maintenances table' => $this->maintenancesData,
            'Migrate maintenances image' => $this->maintenancesImage,
            'Migrate facility timeslot table' => $this->facilityTimeslotsData,
            'Migrate sos management table' => $this->sosManagementsData,
            'Migrate bill payee settings table' => $this->billPayeeSettingsData,
            'Migrate bill payee settings image' => $this->billPayeeSettingsImage,
            'Migrate bill payee bank details table' => $this->billPayeeBankDetailsData,
            'Migrate invoices table' => $this->invoicesData, // items(bill reminder drilldowns) //bill remider slips
            'Migrate slip image' => $this->billSlipsImage,
            'Migrate other amenities table' => $this->otherAmenitiesData,
            'Migrate warranty handbooks image' => $this->warrantyHandbooksImage,
            'Migrate comments table' => $this->commentsData, // migrate with image(having issue to get data in mmb2 coz most of the columns is not unique)
            'Migrate visitor purpose table' => $this->visitorPurposesData,
            'Migrate visitor cards table' => $this->visitorCardsData,
            // 'Migrate 3rd Party visitor cards table' => $this->register3rdPartyVisitorCardData,
            'Migrate visitor remark table' => $this->visitorRemarksData,
            'Migrate visitors table' => $this->visitorsData,
            'Migrate prebook visitor table' => $this->prebookVisitorData,
            'Migrate visitors image' => $this->visitorsImage,
            'Migrate blacklisted visitor table' => $this->blacklistedVisitorsData,
            'Migrate blacklisted visitor image' => $this->blacklistedVisitorsImage,
            'Migrate property management table' => $this->propertyManagementsData,
            'Migrate parking table' => $this->parkingsData, // parking_fees,parking_fee_models,calculations
            'Migrate visitor parking table' => $this->visitorParkingsData, // parking_fees,parking_fee_models,calculations
            'Migrate vouchers image' => $this->vouchersImage,
            'Migrate vehicle table' => $this->vehiclesData,
            'Migrate vehicle image' => $this->vehiclesImage,
            'Migrate pet table' => $this->petsData,
            'Migrate pet image' => $this->petsImage,
            'Migrate model histories table' => $this->modelHistoriesData,

            // //run after all data tested okay (generate home id first)
            // 'Artisan Generate New Home Id' => "generate:homeid $residence_id"
            // 'Artisan Generate New Mmb Id' => "generate:mmbid $residence_id",

            // update is migrated status in mmb1
            // 'Artisan Update Is Migratd Status' => "update:isMigratedStatus --residence_id=$residence_id"

        ];

        $bar = $this->output->createProgressBar(count($migrations));
        $bar->start();

        foreach ($migrations as $key => $migration) {
            if (str_contains($key, 'Artisan')) {
                $benchmarks[] = [$key, Benchmark::measure(fn () => Artisan::call($migration))];
            } else {
                $benchmarks[] = [$key, Benchmark::measure(fn () => $migration->execute($residence_id))];
            }

            $bar->advance();
            $this->newLine();
            $this->line($key);
        }
        $bar->finish();

        $this->newLine(1);
        $this->table(
            ['Key', 'Benchmark (ms)'],
            $benchmarks
        );

        $time = microtime(true) - $start;
        $this->newLine(1);
        $this->line('Total executed time: '.$time);
        $this->newLine(1);

        return Command::SUCCESS;
    }
}
