<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BuyerNeedController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CoopController;
use App\Http\Controllers\EnquiryController;
use App\Http\Controllers\FarmController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\LossController;
use App\Http\Controllers\MarketController;
use App\Http\Controllers\MfaController;
use App\Http\Controllers\MyDataController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PriceController;
use App\Http\Controllers\SavedSupplierController;
use App\Http\Controllers\StorageController;
use App\Http\Controllers\TransportController;
use Illuminate\Support\Facades\Route;

// ---- Public -------------------------------------------------------------------------------
Route::get('/', [HomeController::class, 'welcome'])->name('welcome');
Route::get('/locale/{locale}', [HomeController::class, 'locale'])->name('locale');
Route::get('/offline', fn () => view('offline'))->name('offline');
Route::get('/health', [ApiController::class, 'health'])->name('health');
Route::middleware('throttle:public')->group(function () {
    Route::get('/prices', [PriceController::class, 'index'])->name('prices.index');
    Route::get('/api/v1/prices', [ApiController::class, 'prices'])->name('api.prices');
});

// ---- Guest --------------------------------------------------------------------------------
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::get('/register', [AuthController::class, 'chooseRole'])->name('register');
    Route::get('/register/{role}', [AuthController::class, 'showRegister'])->name('register.role');
    Route::post('/register/{role}', [AuthController::class, 'register'])->middleware('throttle:register')->name('register.store');
    Route::get('/forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'email'])->middleware('throttle:login')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'form'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->middleware('throttle:login')->name('password.update');
});

// ---- Authenticated (MFA enforced for coop and platform admins) ---------------------------------
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/mfa/setup', [MfaController::class, 'setup'])->name('mfa.setup');
    Route::post('/mfa/setup', [MfaController::class, 'confirm'])->middleware('throttle:mfa')->name('mfa.confirm');
    Route::get('/mfa/challenge', [MfaController::class, 'challenge'])->name('mfa.challenge');
    Route::post('/mfa/challenge', [MfaController::class, 'verify'])->middleware('throttle:mfa')->name('mfa.verify');
});

Route::middleware(['auth', 'mfa', 'throttle:writes'])->group(function () {
    Route::get('/home', [HomeController::class, 'home'])->name('home');
    Route::get('/more', [HomeController::class, 'more'])->name('more');

    // Notifications and personal data (every role)
    Route::get('/alerts', [NotificationController::class, 'index'])->name('alerts.index');
    Route::post('/alerts/read-all', [NotificationController::class, 'readAll'])->name('alerts.read-all');
    Route::get('/alerts/{id}/open', [NotificationController::class, 'read'])->name('alerts.open');
    Route::post('/alerts/prefs', [NotificationController::class, 'prefs'])->name('alerts.prefs');
    Route::get('/my-data', [MyDataController::class, 'index'])->name('my-data.index');
    Route::get('/my-data/export.json', [MyDataController::class, 'json'])->middleware('throttle:exports')->name('my-data.json');
    Route::get('/my-data/export.csv', [MyDataController::class, 'csv'])->middleware('throttle:exports')->name('my-data.csv');
    Route::post('/my-data/delete', [MyDataController::class, 'requestDeletion'])->name('my-data.delete');
    Route::delete('/my-data/delete', [MyDataController::class, 'cancelDeletion'])->name('my-data.cancel');

    // Prices: submitting a user price (reading is public)
    Route::post('/prices/user', [PriceController::class, 'store'])->name('prices.user.store');

    // Directories readable by any signed-in role
    Route::get('/storage', [StorageController::class, 'index'])->name('storage.index');
    Route::get('/transport', [TransportController::class, 'index'])->name('transport.index');
    Route::get('/photos/{photo}', [ListingController::class, 'photoShow'])->name('photos.show');

    // JSON endpoints used by the offline queue
    Route::get('/api/v1/session', [ApiController::class, 'session'])->name('api.session');
    Route::post('/api/v1/sync', [ApiController::class, 'sync'])->middleware('throttle:sync')->name('api.sync');
    Route::get('/api/v1/listings', [ApiController::class, 'listings'])->name('api.listings');

    // Enquiries (farmer + buyer)
    Route::middleware('role:farmer,buyer')->group(function () {
        Route::get('/enquiries', [EnquiryController::class, 'index'])->name('enquiries.index');
        Route::get('/enquiries/{enquiry}', [EnquiryController::class, 'show'])->name('enquiries.show');
        Route::post('/deliveries', [TransportController::class, 'store'])->name('deliveries.store');
        Route::post('/storage/{facility}/request', [StorageController::class, 'request'])->name('storage.request');
    });

    // Farmer
    Route::middleware('role:farmer')->group(function () {
        Route::get('/sell', [ListingController::class, 'create'])->name('listings.create');
        Route::get('/my/listings', [ListingController::class, 'index'])->name('listings.index');
        Route::post('/listings', [ListingController::class, 'store'])->name('listings.store');
        Route::get('/listings/{listing}', [ListingController::class, 'show'])->name('listings.show');
        Route::get('/listings/{listing}/edit', [ListingController::class, 'edit'])->name('listings.edit');
        Route::put('/listings/{listing}', [ListingController::class, 'update'])->name('listings.update');
        Route::post('/listings/{listing}/status', [ListingController::class, 'status'])->name('listings.status');
        Route::delete('/listings/{listing}', [ListingController::class, 'destroy'])->name('listings.destroy');
        Route::post('/listings/{listing}/photos', [ListingController::class, 'photoStore'])->middleware('throttle:uploads')->name('listings.photos.store');
        Route::delete('/photos/{photo}', [ListingController::class, 'photoDestroy'])->name('photos.destroy');
        Route::post('/enquiries/{enquiry}/reply', [EnquiryController::class, 'reply'])->name('enquiries.reply');

        Route::get('/farms', [FarmController::class, 'index'])->name('farms.index');
        Route::post('/farms', [FarmController::class, 'store'])->name('farms.store');
        Route::delete('/farms/{farm}', [FarmController::class, 'destroy'])->name('farms.destroy');
        Route::post('/farms/{farm}/crops', [FarmController::class, 'cropStore'])->name('farms.crops.store');
        Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
        Route::post('/calendar', [CalendarController::class, 'store'])->name('calendar.store');
        Route::delete('/calendar/{entry}', [CalendarController::class, 'destroy'])->name('calendar.destroy');

        Route::get('/losses', [LossController::class, 'index'])->name('losses.index');
        Route::post('/losses', [LossController::class, 'store'])->name('losses.store');
        Route::post('/losses/lots', [LossController::class, 'lotStore'])->name('losses.lots.store');
        Route::get('/losses/report', [LossController::class, 'report'])->name('losses.report');
        Route::get('/losses/export.csv', [LossController::class, 'export'])->middleware('throttle:exports')->name('losses.export');
    });
    // enquiry status: farmer (agree/decline) or buyer (withdraw); policy decides
    Route::post('/enquiries/{enquiry}/status', [EnquiryController::class, 'status'])->middleware('role:farmer,buyer')->name('enquiries.status');
    Route::post('/enquiries', [EnquiryController::class, 'store'])->middleware('role:buyer')->name('enquiries.store');

    // Buyer
    Route::middleware('role:buyer')->group(function () {
        Route::get('/market', [MarketController::class, 'index'])->name('market.index');
        Route::get('/market/compare', [MarketController::class, 'compare'])->name('market.compare');
        Route::get('/market/{listing}', [MarketController::class, 'show'])->name('market.show');
        Route::get('/saved', [SavedSupplierController::class, 'index'])->name('saved.index');
        Route::post('/saved/{supplier}', [SavedSupplierController::class, 'toggle'])->name('saved.toggle');
        Route::get('/needs', [BuyerNeedController::class, 'index'])->name('needs.index');
        Route::get('/needs/new', [BuyerNeedController::class, 'create'])->name('needs.create');
        Route::post('/needs', [BuyerNeedController::class, 'store'])->name('needs.store');
        Route::get('/needs/{need}', [BuyerNeedController::class, 'show'])->name('needs.show');
        Route::post('/needs/{need}/close', [BuyerNeedController::class, 'close'])->name('needs.close');
    });

    // Storage provider
    Route::middleware('role:storage_provider')->group(function () {
        Route::get('/storage/mine', [StorageController::class, 'mine'])->name('storage.mine');
        Route::get('/storage/new', [StorageController::class, 'create'])->name('storage.create');
        Route::post('/storage', [StorageController::class, 'store'])->name('storage.store');
        Route::get('/storage/{facility}/edit', [StorageController::class, 'edit'])->name('storage.edit');
        Route::put('/storage/{facility}', [StorageController::class, 'update'])->name('storage.update');
        Route::get('/storage-requests', [StorageController::class, 'requests'])->name('storage.requests');
        Route::post('/storage-requests/{req}/status', [StorageController::class, 'requestStatus'])->name('storage.requests.status');
    });

    // Transporter
    Route::middleware('role:transporter')->group(function () {
        Route::get('/transport/profile', [TransportController::class, 'profile'])->name('transport.profile');
        Route::post('/transport/profile', [TransportController::class, 'profileSave'])->name('transport.profile.save');
    });
    // Deliveries visible to transporters too; status changes by carrier or requester (policy)
    Route::get('/deliveries/{delivery}', [TransportController::class, 'show'])->name('deliveries.show');
    Route::post('/deliveries/{delivery}/status', [TransportController::class, 'status'])->name('deliveries.status');
    Route::get('/deliveries', [TransportController::class, 'deliveries'])->middleware('role:farmer,buyer,transporter')->name('deliveries.index');

    // Cooperative admin
    Route::middleware('role:coop_admin')->prefix('coop')->name('coop.')->group(function () {
        Route::get('/', [CoopController::class, 'index'])->name('index');
        Route::get('/members', [CoopController::class, 'members'])->name('members');
        Route::get('/reports', [CoopController::class, 'reports'])->name('reports');
        Route::get('/export.csv', [CoopController::class, 'export'])->middleware('throttle:exports')->name('export');
    });

    // Platform admin
    Route::middleware('role:platform_admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('index');
        Route::get('/users', [AdminController::class, 'users'])->name('users');
        Route::post('/users/{user}/suspend', [AdminController::class, 'suspend'])->name('suspend');
        Route::get('/verify', [AdminController::class, 'verify'])->name('verify');
        Route::post('/verify', [AdminController::class, 'verifyAction'])->name('verify.action');
        Route::get('/audit', [AdminController::class, 'audit'])->name('audit');
        Route::get('/deletions', [AdminController::class, 'deletions'])->name('deletions');
        Route::post('/deletions/{req}/process', [AdminController::class, 'processDeletion'])->name('deletions.process');
        Route::get('/prices', [AdminController::class, 'prices'])->name('prices');
    });
});
