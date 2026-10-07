<?php

use App\Filament\Pages\Auth\CustomLogin;
use App\Filament\Pages\PatrolCheckpoints\QrCheckpoint;
use App\Filament\Resources\PrebookVisitors\Pages\QrPrebookVisitor;
use App\Filament\Resources\VisitorCards\Pages\QrVisitorCard;
use App\Http\Controllers\BillController;
use App\Http\Controllers\BillTransactionController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DailyActivityReportController;
use App\Http\Controllers\IncidentReportController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\ParcelQrController;
use App\Http\Controllers\PatrolCheckPointLogController;
use App\Http\Controllers\PreregisterVisitorController;
use App\Http\Controllers\PrivacyPolicyController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\TermController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VisitorController;
use App\Http\Controllers\VisitorQrController;
use App\Models\VisitorLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/login', CustomLogin::class)->name('login');

Route::get('/', function () {
    return redirect('admin');
});

Route::any('/graphql', function () {
    return response()->json(['error' => 'GraphQL endpoint is blocked'], 403);
});

require __DIR__.'/auth.php';

Route::prefix('privacy')->name('privacy-policy.')->group(function () {
    Route::get('/', [PrivacyPolicyController::class, 'index'])->name('index');
    Route::get('/th', [PrivacyPolicyController::class, 'indexth'])->name('indexth');
});

Route::prefix('terms')->name('terms.')->group(function () {
    Route::get('/', [TermController::class, 'index'])->name('index');
    Route::get('/th', [TermController::class, 'indexth'])->name('indexth');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('dashboard', function () {
        return redirect('admin');
        // return redirect()->route('filament.pages.dashboard');
    })->name('dashboard');

    Route::get('/qr/{visitorCardId}', QrVisitorCard::class);
    Route::get('/qr/{preregisterVisitorId}', QrPrebookVisitor::class);

    Route::get('/patrol-guard-checkpoint/qr/{checkpointId}', QrCheckpoint::class)->name('qr-checkpoint');
    Route::get('/maintenances/{id}/private-export-details', [MaintenanceController::class, 'exportPrivateMaintenanceDetailReport'])->name('maintenance.private-export-report-detail');
    Route::get('/maintenances/{id}/public-export-details', [MaintenanceController::class, 'exportPublicMaintenanceDetailReport'])->name('maintenance.public-export-report-detail');

    Route::post('/support-tickets/comment', [SupportTicketController::class, 'comment'])->name('support-tickets.comment');

    Route::get('/daily-activity-reports/{dailyActivityReport}', [DailyActivityReportController::class, 'report'])->name('daily-activity-report.report');
});

Route::get('/lang/{locale}', function ($locale) {
    $languages = array_keys(config('languages', []));

    if (! in_array($locale, $languages)) {
        abort(400);
    }

    if (Auth::check()) {
        Auth::user()->lang = $locale;
        Auth::user()->save();
    }

    session(['locale' => $locale]);

    return redirect()->back();
})->name('lang.switch');

Route::post('/maintenances/{id}/comment', [CommentController::class, 'store'])->name('maintenance-comments.store');
Route::post('/users/profile/{id}', [ProfileController::class, 'update'])->name('profile.update');

Route::post('/units/{id}/regenerate-invitation-code-owner', [UnitController::class, 'regenerateInvitationCodeOwner'])->name('unit-invitation-code-owner.regenerate');
Route::post('/units/{id}/regenerate-invitation-code-tenant', [UnitController::class, 'regenerateInvitationCodeTenant'])->name('unit-invitation-code-tenant.regenerate');
Route::post('/visitors/{id}/estamp', [VisitorController::class, 'estamp'])->name('visitors.estamp');
Route::get('/visitors/{visitor_code}', [VisitorQrController::class, 'visitorQR'])->name('visitors.qr');
Route::get('/parcels/{qr_code}', [ParcelQrController::class, 'parcelQR'])->name('parcels.qr');

Route::get('/bill-reminders/invoice/{invoice}', [BillController::class, 'invoice'])->name('bill-reminder.invoice');
Route::group(['prefix' => 'bill-reminder-slips'], function () {
    Route::get('/receipt/{transaction}', [BillTransactionController::class, 'show'])->name('bill-reminder-slip.receipt');
    Route::get('/{transaction}/export', [BillTransactionController::class, 'export'])->name('bill-reminder-slip.export');
});
Route::get('/preregister-visitors/print-qr-bulk', [PreregisterVisitorController::class, 'printQRBulk'])->name('preregister.print.qr');

Route::get('/users/verify/{id}', [UserController::class, 'verification'])->name('user.verify');

Route::get('/export', function () {
    $visitorLogs = VisitorLog::factory()->count(10)->create();
    // Excel::store(new VisitorsASRExport(AutoSendReport::first(), Carbon::now()), "test1.xlsx");
});

Route::post('/patrol-checkpoints/export', [PatrolCheckPointLogController::class, 'export'])->name('patrol-checkpoint.export');
Route::post('/incidents-report/export', [IncidentReportController::class, 'export'])->name('incident-report.export');

Route::get('/account/delete', function () {
    return view('acc-deletion-playstore');
});
