<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceCorrectionController;
use App\Http\Controllers\AttendanceLogController;
use App\Http\Controllers\AttendanceReportController;
use App\Http\Controllers\AttendanceScannerController;
use App\Http\Controllers\AttendanceScheduleController;
use App\Http\Controllers\AttendanceSettingController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\BorrowController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ConditionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\Employee\AttendanceController as EmployeeAttendanceController;
use App\Http\Controllers\Employee\CalendarController as EmployeeCalendarController;
use App\Http\Controllers\Employee\CorrectionRequestController as EmployeeCorrectionRequestController;
use App\Http\Controllers\Employee\DashboardController as EmployeeDashboardController;
use App\Http\Controllers\Employee\DtrController as EmployeeDtrController;
use App\Http\Controllers\Employee\EmployeeLoginController;
use App\Http\Controllers\Employee\NotificationController as EmployeeNotificationController;
use App\Http\Controllers\Employee\PasswordController as EmployeePasswordController;
use App\Http\Controllers\Employee\ProfileController as EmployeeProfileController;
use App\Http\Controllers\Employee\QrController as EmployeeQrController;
use App\Http\Controllers\Employee\ScheduleController as EmployeeScheduleController;
use App\Http\Controllers\EmployeeAttendanceQrController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ImportExportController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\QrController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('i/{qr_code}', [QrController::class, 'publicProfile'])->name('qr.public');

Route::middleware('guest')->group(function (): void {
    Route::get('login', [LoginController::class, 'showLogin'])->name('login');
    Route::post('login', [LoginController::class, 'login']);

    Route::prefix('employee')->name('employee.')->group(function (): void {
        Route::get('login', [EmployeeLoginController::class, 'showLogin'])->name('login');
        Route::post('login', [EmployeeLoginController::class, 'login'])->name('login.submit');
        Route::get('forgot-password', [EmployeeLoginController::class, 'showForgotPassword'])->name('password.request');
        Route::post('forgot-password', [EmployeeLoginController::class, 'sendResetLink'])->name('password.email');
        Route::get('reset-password/{token}', [EmployeeLoginController::class, 'showResetPassword'])->name('password.reset');
        Route::post('reset-password', [EmployeeLoginController::class, 'resetPassword'])->name('password.update');
    });
});

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::post('logout', [LoginController::class, 'logout'])->name('logout');

    // Employee Portal
    Route::middleware('employee')->prefix('employee')->name('employee.')->group(function (): void {
        Route::post('logout', [EmployeeLoginController::class, 'logout'])->name('logout');
        Route::get('/', [EmployeeDashboardController::class, 'index'])->name('dashboard');
        Route::get('live', [EmployeeDashboardController::class, 'live'])->name('live');

        Route::get('attendance', [EmployeeAttendanceController::class, 'index'])->name('attendance');
        Route::get('dtr', [EmployeeDtrController::class, 'index'])->name('dtr');
        Route::get('dtr/export', [EmployeeDtrController::class, 'export'])->name('dtr.export');
        Route::get('calendar', [EmployeeCalendarController::class, 'index'])->name('calendar');
        Route::get('calendar/day', [EmployeeCalendarController::class, 'day'])->name('calendar.day');
        Route::get('schedule', [EmployeeScheduleController::class, 'index'])->name('schedule');

        Route::get('qr', [EmployeeQrController::class, 'show'])->name('qr');
        Route::get('qr/download', [EmployeeQrController::class, 'download'])->name('qr.download');
        Route::get('qr/print', [EmployeeQrController::class, 'print'])->name('qr.print');

        Route::get('corrections', [EmployeeCorrectionRequestController::class, 'index'])->name('corrections.index');
        Route::get('corrections/create', [EmployeeCorrectionRequestController::class, 'create'])->name('corrections.create');
        Route::post('corrections', [EmployeeCorrectionRequestController::class, 'store'])->name('corrections.store');

        Route::get('notifications', [EmployeeNotificationController::class, 'index'])->name('notifications');
        Route::get('notifications/unread-count', [EmployeeNotificationController::class, 'unreadCount'])->name('notifications.unread');
        Route::post('notifications/{notification}/read', [EmployeeNotificationController::class, 'markRead'])->name('notifications.read');
        Route::post('notifications/read-all', [EmployeeNotificationController::class, 'markAllRead'])->name('notifications.read-all');

        Route::get('profile', [EmployeeProfileController::class, 'show'])->name('profile');
        Route::put('profile', [EmployeeProfileController::class, 'update'])->name('profile.update');

        Route::get('password', [EmployeePasswordController::class, 'edit'])->name('password.edit');
        Route::put('password', [EmployeePasswordController::class, 'update'])->name('password.change');
    });

    // Admin / Staff / Viewer — employees redirected away
    Route::middleware('not_employee')->group(function (): void {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('dashboard/charts', [DashboardController::class, 'charts'])->name('dashboard.charts');

        Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::get('inventory/export', [InventoryController::class, 'export'])->name('inventory.export');

        Route::middleware('role:admin,staff')->group(function (): void {
            Route::get('inventory/create', [InventoryController::class, 'create'])->name('inventory.create');
            Route::post('inventory', [InventoryController::class, 'store'])->name('inventory.store');
        });

        Route::get('inventory/{item}', [InventoryController::class, 'show'])->name('inventory.show')->whereNumber('item');

        Route::get('scan', [QrController::class, 'scan'])->name('qr.scan');
        Route::post('scan/lookup', [QrController::class, 'scanLookup'])->name('qr.scan.lookup');

        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread');
        Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
        Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/{type}/export', [ReportController::class, 'export'])->name('reports.export');
        Route::get('reports/{type}', [ReportController::class, 'show'])->name('reports.show');

        Route::resource('categories', CategoryController::class);
        Route::resource('locations', LocationController::class);
        Route::resource('departments', DepartmentController::class);
        Route::resource('suppliers', SupplierController::class);

        Route::middleware('role:admin,staff')->group(function (): void {
            Route::get('inventory/{item}/edit', [InventoryController::class, 'edit'])->name('inventory.edit')->whereNumber('item');
            Route::put('inventory/{item}', [InventoryController::class, 'update'])->name('inventory.update')->whereNumber('item');
            Route::post('inventory/{item}/archive', [InventoryController::class, 'archive'])->name('inventory.archive')->whereNumber('item');

            Route::get('inventory/{item}/qr', [QrController::class, 'show'])->name('qr.show')->whereNumber('item');
            Route::get('inventory/{item}/qr/download', [QrController::class, 'download'])->name('qr.download')->whereNumber('item');
            Route::get('inventory/{item}/qr/print', [QrController::class, 'print'])->name('qr.print.single')->whereNumber('item');

            Route::get('qr/batch', [QrController::class, 'batchForm'])->name('qr.batch');
            Route::post('qr/batch', [QrController::class, 'batchGenerate'])->name('qr.batch.generate');

            Route::get('inventory/{item}/stock-in', [StockController::class, 'stockInForm'])->name('stock.in.form')->whereNumber('item');
            Route::post('inventory/{item}/stock-in', [StockController::class, 'stockIn'])->name('stock.in')->whereNumber('item');
            Route::get('inventory/{item}/stock-out', [StockController::class, 'stockOutForm'])->name('stock.out.form')->whereNumber('item');
            Route::post('inventory/{item}/stock-out', [StockController::class, 'stockOut'])->name('stock.out')->whereNumber('item');
            Route::get('inventory/{item}/return-stock', [StockController::class, 'returnForm'])->name('stock.return.form')->whereNumber('item');
            Route::post('inventory/{item}/return-stock', [StockController::class, 'returnStock'])->name('stock.return')->whereNumber('item');
            Route::get('inventory/{item}/adjust', [StockController::class, 'adjustForm'])->name('stock.adjust.form')->whereNumber('item');
            Route::post('inventory/{item}/adjust', [StockController::class, 'adjust'])->name('stock.adjust')->whereNumber('item');

            Route::get('purchases', [PurchaseController::class, 'index'])->name('purchases.index');
            Route::get('purchases/create', [PurchaseController::class, 'create'])->name('purchases.create');
            Route::post('purchases', [PurchaseController::class, 'store'])->name('purchases.store');
            Route::get('purchases/{purchase}', [PurchaseController::class, 'show'])->name('purchases.show')->whereNumber('purchase');
            Route::get('purchases/{purchase}/edit', [PurchaseController::class, 'edit'])->name('purchases.edit')->whereNumber('purchase');
            Route::put('purchases/{purchase}', [PurchaseController::class, 'update'])->name('purchases.update')->whereNumber('purchase');
            Route::post('purchases/{purchase}/ordered', [PurchaseController::class, 'markOrdered'])->name('purchases.ordered')->whereNumber('purchase');
            Route::get('purchases/{purchase}/receive', [PurchaseController::class, 'receiveForm'])->name('purchases.receive.form')->whereNumber('purchase');
            Route::post('purchases/{purchase}/receive', [PurchaseController::class, 'receive'])->name('purchases.receive')->whereNumber('purchase');
            Route::post('purchases/{purchase}/cancel', [PurchaseController::class, 'cancel'])->name('purchases.cancel')->whereNumber('purchase');

            Route::get('pos', [PosController::class, 'terminal'])->name('pos.terminal');
            Route::get('pos/search', [PosController::class, 'searchItems'])->name('pos.search');
            Route::post('pos/scan', [PosController::class, 'scanItem'])->name('pos.scan');
            Route::post('pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout');
            Route::get('pos/sales', [PosController::class, 'index'])->name('pos.index');
            Route::get('pos/sales/{sale}', [PosController::class, 'show'])->name('pos.show')->whereNumber('sale');
            Route::get('pos/sales/{sale}/receipt', [PosController::class, 'receipt'])->name('pos.receipt')->whereNumber('sale');
            Route::post('pos/sales/{sale}/void', [PosController::class, 'void'])->name('pos.void')->whereNumber('sale');

            Route::get('inventory/{item}/borrow', [BorrowController::class, 'borrowForm'])->name('borrow.create')->whereNumber('item');
            Route::post('inventory/{item}/borrow', [BorrowController::class, 'borrow'])->name('borrow.store')->whereNumber('item');
            Route::get('borrow/{record}/return', [BorrowController::class, 'returnForm'])->name('borrow.return')->whereNumber('record');
            Route::post('borrow/{record}/return', [BorrowController::class, 'returnItem'])->name('borrow.return.store')->whereNumber('record');

            Route::get('inventory/{item}/transfer', [TransferController::class, 'form'])->name('transfer.create')->whereNumber('item');
            Route::post('inventory/{item}/transfer', [TransferController::class, 'store'])->name('transfer.store')->whereNumber('item');

            Route::patch('inventory/{item}/condition', [ConditionController::class, 'update'])->name('condition.update')->whereNumber('item');
        });

        Route::middleware('role:admin,staff')->prefix('attendance')->name('attendance.')->group(function (): void {
            Route::get('/', [AttendanceController::class, 'dashboard'])->name('dashboard');
            Route::get('live', [AttendanceController::class, 'liveStats'])->name('live');
            Route::get('today', [AttendanceController::class, 'today'])->name('today');
            Route::get('currently-in', [AttendanceController::class, 'currentlyIn'])->name('currently-in');
            Route::get('records', [AttendanceController::class, 'records'])->name('records');
            Route::get('records/{record}', [AttendanceController::class, 'show'])->name('records.show')->whereNumber('record');
            Route::get('monthly', [AttendanceController::class, 'monthly'])->name('monthly');

            Route::get('scanner', [AttendanceScannerController::class, 'index'])->name('scanner');
            Route::post('scanner/punch', [AttendanceScannerController::class, 'punch'])->name('scanner.punch');

            Route::get('schedules', [AttendanceScheduleController::class, 'index'])->name('schedules.index');

            Route::get('reports', [AttendanceReportController::class, 'index'])->name('reports.index');
            Route::get('reports/{type}/export', [AttendanceReportController::class, 'export'])->name('reports.export');
            Route::get('reports/{type}', [AttendanceReportController::class, 'show'])->name('reports.show');

            Route::get('scan-logs', [AttendanceLogController::class, 'scanLogs'])->name('scan-logs');
        });

        Route::middleware('role:admin,staff')->group(function (): void {
            Route::get('employees', [EmployeeController::class, 'index'])->name('employees.index');
            Route::get('employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show')->whereNumber('employee');
        });

        Route::middleware('role:admin')->group(function (): void {
            Route::get('employees/create', [EmployeeController::class, 'create'])->name('employees.create');
            Route::post('employees', [EmployeeController::class, 'store'])->name('employees.store');
            Route::get('employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit')->whereNumber('employee');
            Route::put('employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update')->whereNumber('employee');
            Route::delete('employees/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy')->whereNumber('employee');
        });

        Route::middleware('role:admin')->prefix('attendance')->name('attendance.')->group(function (): void {
            Route::get('schedules/create', [AttendanceScheduleController::class, 'create'])->name('schedules.create');
            Route::post('schedules', [AttendanceScheduleController::class, 'store'])->name('schedules.store');
            Route::get('schedules/{schedule}/edit', [AttendanceScheduleController::class, 'edit'])->name('schedules.edit')->whereNumber('schedule');
            Route::put('schedules/{schedule}', [AttendanceScheduleController::class, 'update'])->name('schedules.update')->whereNumber('schedule');
            Route::get('shifts', [AttendanceScheduleController::class, 'shifts'])->name('shifts.index');
            Route::post('shifts', [AttendanceScheduleController::class, 'storeShift'])->name('shifts.store');

            Route::get('corrections', [AttendanceCorrectionController::class, 'index'])->name('corrections.index');
            Route::get('corrections/create', [AttendanceCorrectionController::class, 'create'])->name('corrections.create');
            Route::post('corrections', [AttendanceCorrectionController::class, 'store'])->name('corrections.store');
            Route::get('correction-requests', [AttendanceCorrectionController::class, 'requests'])->name('correction-requests.index');
            Route::post('correction-requests/{correctionRequest}/approve', [AttendanceCorrectionController::class, 'approveRequest'])->name('correction-requests.approve');
            Route::post('correction-requests/{correctionRequest}/reject', [AttendanceCorrectionController::class, 'rejectRequest'])->name('correction-requests.reject');

            Route::get('settings', [AttendanceSettingController::class, 'edit'])->name('settings.edit');
            Route::put('settings', [AttendanceSettingController::class, 'update'])->name('settings.update');
            Route::post('settings/holidays', [AttendanceSettingController::class, 'storeHoliday'])->name('settings.holidays.store');

            Route::get('qr', [EmployeeAttendanceQrController::class, 'index'])->name('qr.index');
            Route::get('qr/{user}', [EmployeeAttendanceQrController::class, 'show'])->name('qr.show')->whereNumber('user');
            Route::post('qr/{user}/generate', [EmployeeAttendanceQrController::class, 'generate'])->name('qr.generate')->whereNumber('user');
            Route::post('qr/{user}/regenerate', [EmployeeAttendanceQrController::class, 'regenerate'])->name('qr.regenerate')->whereNumber('user');
            Route::post('qr/{user}/disable', [EmployeeAttendanceQrController::class, 'disable'])->name('qr.disable')->whereNumber('user');
            Route::get('qr/{user}/download', [EmployeeAttendanceQrController::class, 'download'])->name('qr.download')->whereNumber('user');
            Route::get('qr/{user}/print', [EmployeeAttendanceQrController::class, 'print'])->name('qr.print')->whereNumber('user');

            Route::get('audit-logs', [AttendanceLogController::class, 'auditLogs'])->name('audit-logs');
        });

        Route::middleware('role:admin')->group(function (): void {
            Route::resource('users', UserController::class);
            Route::post('users/{user}/activate', [UserController::class, 'activate'])->name('users.activate');
            Route::post('users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
            Route::get('users/{user}/reset-password', [UserController::class, 'resetPasswordForm'])->name('users.reset-password.form');
            Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');

            Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
            Route::put('settings', [SettingController::class, 'update'])->name('settings.update');

            Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit.index');

            Route::get('import', [ImportExportController::class, 'importForm'])->name('import.index');
            Route::post('import/preview', [ImportExportController::class, 'preview'])->name('import.preview');
            Route::post('import/confirm', [ImportExportController::class, 'confirm'])->name('import.confirm');
            Route::get('export', [ImportExportController::class, 'export'])->name('export.index');

            Route::get('backups', [BackupController::class, 'index'])->name('backups.index');
            Route::post('backups', [BackupController::class, 'create'])->name('backups.create');
            Route::get('backups/{backup}/download', [BackupController::class, 'download'])->name('backups.download');
            Route::post('backups/{backup}/restore', [BackupController::class, 'restore'])->name('backups.restore');
        });
    });
});
