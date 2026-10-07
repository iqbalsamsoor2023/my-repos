<?php

use App\Http\Controllers\Api\V1\ActivationModuleController;
use App\Http\Controllers\Api\V1\AmenityBookingController;
use App\Http\Controllers\Api\V1\AmenityController;
use App\Http\Controllers\Api\V1\AnnouncementController;
use App\Http\Controllers\Api\V1\AppVersionController;
use App\Http\Controllers\Api\V1\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\BillPayeeSettingController;
use App\Http\Controllers\Api\V1\BlacklistedVisitorController;
use App\Http\Controllers\Api\V1\BrandController;
use App\Http\Controllers\Api\V1\CalculationController;
use App\Http\Controllers\Api\V1\ClaimableItemController;
use App\Http\Controllers\Api\V1\CommentController;
use App\Http\Controllers\Api\V1\CompanyController;
use App\Http\Controllers\Api\V1\CountryController;
use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\DistrictController;
use App\Http\Controllers\Api\V1\EmergencyContactController;
use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\Api\V1\FacilityBookingController;
use App\Http\Controllers\Api\V1\FacilityController;
use App\Http\Controllers\Api\V1\FacilityTimeslotController;
use App\Http\Controllers\Api\V1\FrequentlyAskQuestionController;
use App\Http\Controllers\Api\V1\HealthQuestionnaireController;
use App\Http\Controllers\Api\V1\IncidentReportController;
use App\Http\Controllers\Api\V1\InsuranceCompanyController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\ItemController;
use App\Http\Controllers\Api\V1\LogisticPartnerController;
use App\Http\Controllers\Api\V1\LPRController;
use App\Http\Controllers\Api\V1\MaintenanceController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\OtherAmenityController;
use App\Http\Controllers\Api\V1\ParcelController;
use App\Http\Controllers\Api\V1\ParkingController;
use App\Http\Controllers\Api\V1\PetController;
use App\Http\Controllers\Api\V1\PmInvoiceController;
use App\Http\Controllers\Api\V1\PreregisterVisitorController;
use App\Http\Controllers\Api\V1\PrivateClaimCategoryController;
use App\Http\Controllers\Api\V1\PrivateClaimItemController;
use App\Http\Controllers\Api\V1\PrivateClaimItemTitleController;
use App\Http\Controllers\Api\V1\ProvinceController;
use App\Http\Controllers\Api\V1\PublicClaimableItemController;
use App\Http\Controllers\Api\V1\Reports\PGS\DailyReportController;
use App\Http\Controllers\Api\V1\Reports\PGS\ListCheckpointsController;
use App\Http\Controllers\Api\V1\Reports\PGS\PatrolSummaryController;
use App\Http\Controllers\Api\V1\ResidenceAmenityController;
use App\Http\Controllers\Api\V1\ResidenceController;
use App\Http\Controllers\Api\V1\ResidenceFeatureController;
use App\Http\Controllers\Api\V1\SosManagementController;
use App\Http\Controllers\Api\V1\SubdistrictController;
use App\Http\Controllers\Api\V1\SupportTicketCategoryController;
use App\Http\Controllers\Api\V1\SupportTicketController;
use App\Http\Controllers\Api\V1\SupportTicketStatusController;
use App\Http\Controllers\Api\V1\ThaiNationalIDOCRController;
use App\Http\Controllers\Api\V1\TransactionController;
use App\Http\Controllers\Api\V1\UnitController;
use App\Http\Controllers\Api\V1\UnitTenantController;
use App\Http\Controllers\Api\V1\UnitUserController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\UserFamilyController;
use App\Http\Controllers\Api\V1\UserHealthController;
use App\Http\Controllers\Api\V1\UserReactionController;
use App\Http\Controllers\Api\V1\UserTutorialController;
use App\Http\Controllers\Api\V1\VehicleController;
use App\Http\Controllers\Api\V1\VehicleModelController;
use App\Http\Controllers\Api\V1\VisitingArrangementController;
use App\Http\Controllers\Api\V1\VisitorCardController;
use App\Http\Controllers\Api\V1\VisitorController;
use App\Http\Controllers\Api\V1\VisitorParkingController;
use App\Http\Controllers\Api\V1\VisitorPurposeController;
use App\Http\Controllers\Api\V1\VisitorRemarkController;
use App\Http\Controllers\Api\V1\VisitorSettingController;
use App\Http\Controllers\Api\V1\WarrantyController;
use App\Http\Controllers\Api\V1\WarrantySettingController;
use App\Http\Controllers\RentPropertyController;
use App\Http\Controllers\SalePropertyController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1')->middleware(['localization'])->group(function () {
    Route::apiResource('/brands', BrandController::class);
});

Route::prefix('v1')->middleware(['localization'])->group(function () {
    Route::group(['prefix' => '/auth'], function () {
        Route::get('/links', [LoginController::class, 'links']);
        Route::post('/login', [LoginController::class, 'login']);
        Route::post('/forgot-password', [ForgotPasswordController::class, 'store']);
    });
    Route::get('/frequently-ask-questions', [FrequentlyAskQuestionController::class, 'index']);
    Route::post('/units/invitation-code', [UnitController::class, 'invitationCodeValidity']);
    Route::post('/users', [UserController::class, 'store']);
    Route::get('/resend-verify-email/{userId}', [UserController::class, 'resendVerificationEmail']);
    Route::apiResource('/countries', CountryController::class);
});

Route::middleware(['auth:api', 'localization'])->group(function () {
    Route::prefix('v1')->group(function () {
        Route::prefix('pm-invoices')->group(function () {
            Route::get('/', [PmInvoiceController::class, 'index'])->name('api.v1.pminvoices.index');
            Route::get('/{invoice_id}', [PmInvoiceController::class, 'show'])->name('api.v1.pminvoices.show');
            Route::get('/{invoice_id}/download', [PmInvoiceController::class, 'download'])->name('api.v1.pminvoices.download');
        });
        Route::apiResource('/amenities', AmenityController::class);
        Route::apiResource('/app-versions', AppVersionController::class);
        Route::apiResource('/activation-modules', ActivationModuleController::class);
        Route::apiResource('/blacklisted-visitors', BlacklistedVisitorController::class);
        Route::apiResource('/calculations', CalculationController::class);
        Route::apiResource('/claimable-items', ClaimableItemController::class);
        Route::apiResource('/comments', CommentController::class);
        Route::apiResource('/companies-logo', LogisticPartnerController::class);
        Route::post('/devices/remove-device', [DeviceController::class, 'remove']);
        Route::apiResource('/districts', DistrictController::class);
        Route::apiResource('/companies', CompanyController::class);
        Route::get('/insurance-companies', [InsuranceCompanyController::class, 'index']);
        Route::apiResource('/emergency-contacts', EmergencyContactController::class);
        Route::post('/events/{id}/rsvp', [EventController::class, 'rsvp']);
        Route::apiResource('/events', EventController::class);
        Route::apiResource('/health-questionnaires', HealthQuestionnaireController::class);
        Route::apiResource('/items', ItemController::class);
        Route::apiResource('/vehicles', VehicleController::class);
        Route::apiResource('/parkings', ParkingController::class);
        Route::apiResource('/pets', PetController::class);
        Route::apiResource('/provinces', ProvinceController::class);
        Route::apiResource('/public-claimable-items', PublicClaimableItemController::class);
        Route::apiResource('/residence-features', ResidenceFeatureController::class);
        Route::apiResource('/announcements', AnnouncementController::class);
        Route::put('/announcement/{id}/update-read-status', [AnnouncementController::class, 'updateReadStatus']);
        Route::apiResource('/facilities', FacilityController::class);
        Route::apiResource('/facility-timeslots', FacilityTimeslotController::class);
        Route::apiResource('/facility-bookings', FacilityBookingController::class);
        Route::apiResource('/user-healths', UserHealthController::class);
        Route::put('/multiple-parcels', [ParcelController::class, 'update']);
        Route::put('/not-my-parcel/{user_id}/update-read-status', [ParcelController::class, 'updateUnreadWrongParcelNotificationsForUser']);
        Route::apiResource('/parcels', ParcelController::class);
        Route::group(['prefix' => 'users'], function () {
            Route::put('/{id}/profile-picture', [UserController::class, 'updateProfilePicture']);
            Route::put('/{id}/change-password', [UserController::class, 'changePassword']);
            Route::put('/{id}/notification', [UserController::class, 'updateNotification']);
        });
        Route::group(['prefix' => 'residence-amenities'], function () {
            Route::get('/', [ResidenceAmenityController::class, 'index']);
            Route::get('/{modelType}/{id}', [ResidenceAmenityController::class, 'show']);
        });
        Route::group(['prefix' => 'amenity-bookings'], function () {
            Route::get('/', [AmenityBookingController::class, 'index']);
            Route::get('/{identifier}', [AmenityBookingController::class, 'show']);
            Route::post('/', [AmenityBookingController::class, 'store']);
        });
        Route::apiResource('/users', UserController::class)->except(['store']);
        Route::apiResource('/user-families', UserFamilyController::class);
        Route::apiResource('/transactions', TransactionController::class);
        Route::post('/transactions/upload-slip', [TransactionController::class, 'uploadSlip']);
        Route::get('/transactions/{id}/receipt-file', [TransactionController::class, 'downloadReceipt']);
        Route::get('/notifications/unread-notification', [NotificationController::class, 'unreadNotification']);
        Route::apiResource('/notifications', NotificationController::class);
        Route::apiResource('/other-amenities', OtherAmenityController::class);
        Route::get('/invoices/total-unpaid-invoice', [InvoiceController::class, 'totalUnpaidInvoice']);
        Route::apiResource('/invoices', InvoiceController::class);
        Route::get('/invoices/{id}/invoice-file', [InvoiceController::class, 'downloadInvoice']);
        Route::post('/thai-national-id-card/front', [ThaiNationalIDOCRController::class, 'front']);
        Route::apiResource('/visitors', VisitorController::class)->except(['show']);
        Route::apiResource('/visitor-remarks', VisitorRemarkController::class);
        Route::apiResource('/visitor-cards', VisitorCardController::class);
        Route::group(['prefix' => 'visitors'], function () {
            Route::put('/{id}/visiting-arrangement', [VisitorController::class, 'updateVisitingArrangement']);
            Route::put('/visitor-codes/{visitor_code}', [VisitorController::class, 'updateByCode']);
            Route::put('/visitor-cards/{visitor_card_id}', [VisitorController::class, 'updateByVisitorCard']);
            Route::get('/statistic', [VisitorController::class, 'statistic']);
            Route::post('/scan-qr-visitor', [VisitorController::class, 'scanInScanOut']);
            Route::get('/prebook-visitors', [VisitorController::class, 'prebookVisitor']);
        });
        Route::apiResource('/visiting-arrangements', VisitingArrangementController::class);
        Route::apiResource('/preregister-visitors', PreregisterVisitorController::class);
        Route::apiResource('/visitor-purposes', VisitorPurposeController::class);
        Route::apiResource('/license-plate-recognition', LPRController::class);
        Route::apiResource('/sos-managements', SosManagementController::class);
        Route::apiResource('/subdistricts', SubdistrictController::class);
        Route::apiResource('/visitor-parkings', VisitorParkingController::class)->except(['show']);
        Route::get('/visitor-parkings/summary', [VisitorParkingController::class, 'calculationSummary']);
        Route::apiResource('/maintenances', MaintenanceController::class);
        Route::post('/maintenances/{id}/comment', [MaintenanceController::class, 'comment']);
        Route::apiResource('/unit-tenants', UnitTenantController::class);
        Route::apiResource('/residences', ResidenceController::class);
        Route::group(['prefix' => 'support-tickets'], function () {
            Route::post('/read-status/{id}', [SupportTicketController::class, 'read']);
            Route::get('/list', [SupportTicketController::class, 'ticketList']);
            Route::get('/comments', [SupportTicketController::class, 'showComment']);
            Route::get('/unread-count/{user_id}', [SupportTicketController::class, 'unreadCount']);
            Route::get('/unread-count/pm/{user_id}', [SupportTicketController::class, 'unreadCountByPm']);
            Route::post('/comments', [SupportTicketController::class, 'comment']);
            Route::put('/comment/read-status/{id}', [SupportTicketController::class, 'readComment']);
        });
        Route::apiResource('/support-tickets', SupportTicketController::class);
        Route::apiResource('/support-ticket-statuses', SupportTicketStatusController::class);
        Route::apiResource('/support-ticket-categories', SupportTicketCategoryController::class)->except(['show']);
        Route::get('/support-ticket-categories/topics', [SupportTicketCategoryController::class, 'indexCategoryTopic']);
        Route::apiResource('/unit-users', UnitUserController::class);
        Route::apiResource('/incident-reports', IncidentReportController::class);
        // PGS Export on PM Talk
        Route::group(['prefix' => 'reports'], function () {
            Route::group(['prefix' => 'pgs'], function () {
                Route::get('checkpoints', [ListCheckpointsController::class, 'index']);
                Route::get('patrol-summary', [PatrolSummaryController::class, 'index']);

                // pgs summary
                Route::get('/checkpoint-summary', [PatrolSummaryController::class, 'getCheckpointSummary']);
                Route::get('/round-summary', [PatrolSummaryController::class, 'getRoundSummary']);
            });
        });
        Route::group(['prefix' => 'reports'], function () {
            Route::get('daily-report', [DailyReportController::class, 'index'])->name('reports.daily-report');
        });

        Route::get('/warranty-reminders', [WarrantyController::class, 'reminder']);
        Route::post('/warranty-reminders', [WarrantyController::class, 'store']);
        Route::apiResource('/warranty-settings', WarrantySettingController::class);
        Route::apiResource('/user-tutorials', UserTutorialController::class);
        Route::apiResource('/vehicle-models', VehicleModelController::class);

        Route::group(['prefix' => 'sale-property'], function () {
            Route::post('/', [SalePropertyController::class, 'store']);
            Route::get('/', [SalePropertyController::class, 'index']);
        });

        Route::group(['prefix' => 'rent-property'], function () {
            Route::post('/', [RentPropertyController::class, 'store']);
            Route::get('/', [RentPropertyController::class, 'index']);
        });
        Route::get('/bank-accounts', [BillPayeeSettingController::class, 'residenceBankAccountList']);
        Route::post('/user-reactions', [UserReactionController::class, 'store']);

        Route::get('/private-claim-categories', [PrivateClaimCategoryController::class, 'index']);
        Route::get('/private-claim-items', [PrivateClaimItemController::class, 'index']);
        Route::get('/private-claim-item-titles', [PrivateClaimItemTitleController::class, 'index']);
    });
});

Route::middleware(['client', 'localization'])->group(function () {
    Route::get('test', function () {
        return ['message' => 'Success'];
    });

    Route::prefix('v1')->group(function () {
        Route::apiResource('/activation-modules', ActivationModuleController::class);
        Route::apiResource('/app-versions', AppVersionController::class);
        Route::apiResource('/preregister-visitors', PreregisterVisitorController::class);
        Route::apiResource('/announcements', AnnouncementController::class);
        Route::get('/blacklisted-visitors', [BlacklistedVisitorController::class, 'index']);
        Route::get('/calculations', [CalculationController::class, 'index']);
        Route::apiResource('/comments', CommentController::class);
        Route::apiResource('/companies', CompanyController::class);
        Route::apiResource('/companies-logo', LogisticPartnerController::class);
        Route::apiResource('/districts', DistrictController::class);
        Route::apiResource('/emergency-contacts', EmergencyContactController::class);
        Route::apiResource('/invoices', InvoiceController::class);
        Route::post('/multiple-parcels', [ParcelController::class, 'update']);
        Route::get('/parkings', [ParkingController::class, 'index']);
        Route::apiResource('/parcels', ParcelController::class);
        Route::apiResource('/residences', ResidenceController::class);
        Route::get('/residence-features', [ResidenceFeatureController::class, 'index']);
        Route::put('/users/{id}/profile-picture', [UserController::class, 'updateProfilePicture']);
        Route::put('/users/{id}/notification', [UserController::class, 'updateNotification']);
        Route::get('/search-user', [UserController::class, 'search']);
        Route::apiResource('/users', UserController::class)->except(['store']);
        Route::group(['prefix' => 'notifications'], function () {
            Route::get('/unread-notification', [NotificationController::class, 'unreadNotification']);
            Route::post('/incident-report', [NotificationController::class, 'notifyResidentOfIncidentReport']);
        });
        Route::apiResource('/notifications', NotificationController::class);
        Route::apiResource('/vehicles', VehicleController::class);
        Route::apiResource('/visitor-cards', VisitorCardController::class);
        Route::group(['prefix' => 'visitors'], function () {
            Route::put('/{id}/visiting-arrangement', [VisitorController::class, 'updateVisitingArrangement']);
            Route::put('/visitor-codes/{visitor_code}', [VisitorController::class, 'updateByCode']);
            Route::put('/visitor-cards/{visitor_card_id}', [VisitorController::class, 'updateByVisitorCard']);
            Route::get('/statistic', [VisitorController::class, 'statistic']);
            Route::post('/scan-qr-visitor', [VisitorController::class, 'scanInScanOut']);
            Route::get('/prebook-visitors', [VisitorController::class, 'prebookVisitor']);
            Route::get('/feedbacks', [VisitorController::class, 'getFeedbackType']);
            Route::post('/feedbacks', [VisitorController::class, 'createFeedback']);
        });
        Route::apiResource('/visitors', VisitorController::class);
        Route::apiResource('/visitor-remarks', VisitorRemarkController::class);
        Route::apiResource('/visiting-arrangements', VisitingArrangementController::class);
        Route::apiResource('/sos-managements', SosManagementController::class);
        Route::apiResource('/visitor-parkings', VisitorParkingController::class)->except(['show']);
        Route::get('/visitor-parkings/summary', [VisitorParkingController::class, 'calculationSummary']);
        Route::apiResource('/maintenances', MaintenanceController::class);
        // Route::apiResource('/checkpoints', CheckpointController::class);
        Route::post('/maintenances/{id}/comment', [MaintenanceController::class, 'comment']);
        Route::apiResource('/provinces', ProvinceController::class);
        Route::apiResource('/subdistricts', SubdistrictController::class);
        Route::apiResource('/units', UnitController::class);
        Route::apiResource('/unit-users', UnitUserController::class);
        Route::apiResource('/user-tutorials', UserTutorialController::class);
        Route::apiResource('/visitor-purposes', VisitorPurposeController::class);
        Route::apiResource('/visitor-settings', VisitorSettingController::class);
        Route::apiResource('/vehicle-models', VehicleModelController::class);
        Route::get('/private-claim-categories', [PrivateClaimCategoryController::class, 'index']);
    });
});
