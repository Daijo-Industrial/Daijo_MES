<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeliveryVerificationController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\MasterItemPhotoController;
use App\Http\Controllers\MasterListItemController;
use App\Http\Controllers\NotificationRecepientController;
use App\Http\Controllers\OperatorUserController;
use App\Http\Controllers\ProductionDashboardController;
use App\Http\Controllers\Setting\HolidayScheduleController;
use App\Http\Controllers\UpdateDailyController;
use App\Http\Controllers\WaitingPurchaseOrderController;
use App\Livewire\Admin\UserActivityDashboard;
use App\Mail\DailyWaitingPurchaseOrders;
use App\Models\WaitingPurchaseOrder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

// ============================================
// PUBLIC ADMIN & OPERATOR USER ROUTES
// ============================================

// Route untuk delivery verification controller
Route::get('/delivery-verification', [DeliveryVerificationController::class, 'index'])->name('delivery.verification');
Route::post('/delivery-verification', [DeliveryVerificationController::class, 'check'])->name('delivery.verification.check');

// Route untuk photo master item
Route::get('/master-items-photo', [MasterItemPhotoController::class, 'index'])->name('master.items');
Route::post('/master-items-photo/{itemCode}/upload', [MasterItemPhotoController::class, 'upload'])->name('master.items.upload');

// Route untuk ubah status DIC oleh admin
Route::post('/admin/dailyitemcodes/{id}/set-status', [ProductionDashboardController::class, 'setStatus'])->name('dailyitemcodes.set-status');

// Route untuk view admin daily item code
Route::get('/admin/dailyitemcodes', [ProductionDashboardController::class, 'adminView'])->name('admin.dailyitemcodes');

// Route untuk handle operator dan operator baru
Route::get('/id-card/operator', [OperatorUserController::class, 'showIdCard']);
Route::get('/operator-users/qr-codes', [OperatorUserController::class, 'showQr']);
Route::get('/operator-users/upload', [OperatorUserController::class, 'uploadForm'])->name('operator-users.upload');
Route::post('/operator-users/import', [OperatorUserController::class, 'import'])->name('operator-users.import');
Route::get('/operator-users/show-all', [OperatorUserController::class, 'showAllOperator'])->name('show.all.operators');
Route::get('/operator/create', [OperatorUserController::class, 'createOperator'])->name('operator.create');
Route::post('/operator/store', [OperatorUserController::class, 'storeOperator'])->name('operator.store');
Route::put('/operator/update-profile-picture', [OperatorUserController::class, 'updateProfilePicture'])->name('operator.updateProfilePicture');
Route::get('/operator-users', [OperatorUserController::class, 'index'])->name('operator.index');

Route::get('/external-users', [UpdateDailyController::class, 'getUsers']);

// Route untuk handle Zone mesin
Route::get('/zone/edit', [OperatorUserController::class, 'editZone'])->name('zone.edit');
Route::post('/zone/update', [OperatorUserController::class, 'updateZone'])->name('zone.update');

// FITUR ADMIN OPERATOR 5 NOVEMBER 2025
Route::get('adminoperator', [DashboardController::class, 'operatoradmin'])->middleware(['auth', 'verified'])->name('adminoperator');
Route::delete('/hourly-remarks/{id}', [DashboardController::class, 'deleteremark'])->name('hourlyremarks.destroy');
Route::post('/storedic', [DashboardController::class, 'createFromAdmin'])->name('dicadmin.store');
Route::delete('/dailyitemcodesdelete/{id}', [DashboardController::class, 'deletedic'])->name('dailyitemcodes.delete');

// ============================================
// AUTHENTICATED ADMIN & SETTING ROUTES
// ============================================
Route::middleware('auth')->group(function () {
    // Role, permissions, branches & departments
    Route::get('/user-role-manager', function () {
        if (Gate::denies('manage-users-roles')) {
            abort(403);
        }
        $currentTab = request()->query('tab', 'users');

        return view('admin.user_role_manager.index', compact('currentTab'));
    })->name('admin.user-role-manager');

    Route::get('/admin/activity-logs', UserActivityDashboard::class)->name('admin.activity-logs');

    Route::get('/admin/branches-departments', function () {
        if (Gate::denies('manage-branches-departments')) {
            abort(403);
        }

        return view('admin.branches_departments.index');
    })->name('admin.branches-departments');

    Route::get('/admin/plant-departments', function () {
        if (Gate::denies('manage-branches-departments')) {
            abort(403);
        }

        return view('admin.branches_departments.index', ['initialTab' => 'plant-departments']);
    })->name('admin.plant-departments');

    // File handling
    Route::post('file/upload', [FileController::class, 'upload'])->name('file.upload');
    Route::delete('/file/{id}', [FileController::class, 'destroy'])->name('file.delete');

    // Waiting Purchase Orders
    Route::resource('waiting_purchase_orders', WaitingPurchaseOrderController::class);
    Route::patch('waiting_purchase_orders/{waiting_purchase_order}/change-status', [WaitingPurchaseOrderController::class, 'changeStatus'])->name('waiting_purchase_orders.changeStatus');

    Route::get('/daily-waiting-purchase-orders/notification', function () {
        $waitingPurchaseOrders = WaitingPurchaseOrder::all();

        return new DailyWaitingPurchaseOrders($waitingPurchaseOrders);
    });

    // Notification Recipients
    Route::resource('notification_recipients', NotificationRecepientController::class);

    // ROUTE UNTUK FITUR HOLIDAY SCHEDULE
    Route::get('setting/holiday-schedule', [HolidayScheduleController::class, 'index'])->name('setting.holiday-schedule.index');
    Route::get('setting/holiday-schedule/create', [HolidayScheduleController::class, 'create'])->name('holiday-schedule.create');
    Route::post('setting/holiday-schedule/store', [HolidayScheduleController::class, 'store'])->name('holiday-schedule.store');
    Route::put('/holiday-schedule/{id}', [HolidayScheduleController::class, 'update'])->name('holiday-schedule.update');
    Route::get('holiday-schedule/export', [HolidayScheduleController::class, 'export'])->name('holiday-schedule.export');
    Route::post('holiday-schedule/import', [HolidayScheduleController::class, 'import'])->name('holiday-schedule.import');

    // Master List Manager & Customer Delivery
    Route::get('/master-list-manager', [MasterListItemController::class, 'manage'])->name('admin.master-list-manager');
    Route::get('/master-list-logs', [MasterListItemController::class, 'logs'])->name('admin.master-list-logs');
    Route::get('/customer-delivery-manager', [MasterListItemController::class, 'customerDeliveryManage'])->name('admin.customer-delivery-manager');
    Route::get('/customer-delivery-logs', [MasterListItemController::class, 'customerDeliveryLogs'])->name('admin.customer-delivery-logs');
    Route::get('/business-partner-manager', function () {
        return view('master_business_partner.index');
    })->name('admin.business-partner-manager');
});
