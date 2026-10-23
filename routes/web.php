<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PageController as AdminPageController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Install\InstallController;
use App\Http\Controllers\Owner\CenterController;
use App\Http\Controllers\Owner\ClassroomController;
use App\Http\Controllers\Owner\DataExportController;
use App\Http\Controllers\Owner\ExpenseController;
use App\Http\Controllers\Owner\InvoiceController;
use App\Http\Controllers\Owner\ReportController;
use App\Http\Controllers\Owner\RoutineController;
use App\Http\Controllers\Owner\SettingsController;
use App\Http\Controllers\Owner\StaffController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ParentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Work\AttendanceController;
use App\Http\Controllers\Work\ChildController;
use App\Http\Controllers\Work\DiaryController;
use App\Http\Controllers\Work\InventoryController;
use App\Http\Controllers\Work\MenuController;
use App\Http\Controllers\Work\TaskController;
use App\Http\Controllers\Work\TodayController;
use Illuminate\Support\Facades\Route;

// ===== INSTALL WIZARD (chạy 1 LẦN để khởi tạo nền tảng Cloud — tạo Super Admin, KHÔNG tạo nhóm trẻ nào) =====
Route::prefix('install')->name('install.')->group(function () {
    Route::get('/', [InstallController::class, 'welcome'])->name('welcome');
    Route::get('/requirements', [InstallController::class, 'requirements'])->name('requirements');
    Route::get('/database', [InstallController::class, 'showDatabaseForm'])->name('database');
    Route::post('/database', [InstallController::class, 'saveDatabase'])->name('database.save');
    Route::get('/account', [InstallController::class, 'showAccountForm'])->name('account');
    Route::post('/account', [InstallController::class, 'saveAccount'])->name('account.save');
    Route::get('/finish', [InstallController::class, 'finish'])->name('finish');
});

// ===== AUTH =====
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');
    Route::middleware('registration.open')->group(function () {
        Route::get('/dang-ky', [RegisterController::class, 'show'])->name('register');
        Route::post('/dang-ky', [RegisterController::class, 'store'])->name('register.store');
    });

    Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
    Route::middleware('registration.open')->group(function () {
        Route::get('/dang-ky/hoan-tat', [RegisterController::class, 'showGooglePhone'])->name('register.google-phone');
        Route::post('/dang-ky/hoan-tat', [RegisterController::class, 'storeGooglePhone'])->name('register.google-phone.store');
    });

    Route::get('/quen-mat-khau', [ForgotPasswordController::class, 'show'])->name('password.request');
    Route::post('/quen-mat-khau', [ForgotPasswordController::class, 'send'])->name('password.email');
    Route::get('/dat-lai-mat-khau/{token}', [ResetPasswordController::class, 'show'])->name('password.reset');
    Route::post('/dat-lai-mat-khau', [ResetPasswordController::class, 'update'])->name('password.update');
});
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

// ===== TRANG TĨNH — nội dung lấy từ database, Super Admin sửa được =====
Route::get('/gioi-thieu', fn (PageController $c) => $c->show('gioi-thieu'))->name('pages.about');
Route::get('/dieu-khoan-su-dung', fn (PageController $c) => $c->show('dieu-khoan-su-dung'))->name('pages.terms');
Route::get('/chinh-sach-bao-mat', fn (PageController $c) => $c->show('chinh-sach-bao-mat'))->name('pages.privacy');

Route::get('/sitemap.xml', [PageController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [PageController::class, 'robots'])->name('robots');

// ===== TRANG PHỤ HUYNH — xem nhật ký của con qua link bí mật, không cần đăng nhập =====
Route::get('/phu-huynh/{token}', [ParentController::class, 'show'])
    ->where('token', '[A-Za-z0-9]{40}')->middleware('throttle:60,1')->name('parent.show');

// ===== SUPER ADMIN — quản lý toàn bộ nhóm trẻ trên nền tảng =====
Route::middleware(['auth', 'role:platform_admin'])->prefix('sieu-quan-tri')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/nhom-tre/{center}', [DashboardController::class, 'show'])->name('centers.show');
    Route::put('/nhom-tre/{center}/khoa-mo', [DashboardController::class, 'toggleActive'])->name('centers.toggle');
    Route::get('/cai-dat', [AdminSettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/cai-dat', [AdminSettingsController::class, 'update'])->name('settings.update');
    Route::get('/trang/{slug}', [AdminPageController::class, 'edit'])->name('pages.edit');
    Route::put('/trang/{slug}', [AdminPageController::class, 'update'])->name('pages.update');
    Route::get('/nguoi-dung', [AdminUserController::class, 'index'])->name('users.index');
    Route::put('/nguoi-dung/{user}/mat-khau', [AdminUserController::class, 'resetPassword'])->name('users.password');
    Route::put('/nguoi-dung/{user}/khoa-mo', [AdminUserController::class, 'toggleActive'])->name('users.toggle');
});

Route::get('/', [HomeController::class, 'index'])->name('home');

// ===== KHU VỰC LÀM VIỆC (chủ nhóm + cô giáo) =====
Route::middleware('auth')->group(function () {
    Route::get('/hom-nay', [TodayController::class, 'index'])->name('today');

    // Điểm danh
    Route::get('/diem-danh', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/diem-danh', [AttendanceController::class, 'mark'])->name('attendance.mark');
    Route::post('/diem-danh/tat-ca', [AttendanceController::class, 'markAll'])->name('attendance.mark-all');
    Route::post('/diem-danh/{attendance}/ve', [AttendanceController::class, 'checkOut'])->whereNumber('attendance')->name('attendance.check-out');
    Route::put('/diem-danh/{attendance}', [AttendanceController::class, 'updateTimes'])->whereNumber('attendance')->name('attendance.update');
    Route::delete('/diem-danh/{attendance}', [AttendanceController::class, 'destroy'])->whereNumber('attendance')->name('attendance.destroy');

    // Công việc
    Route::get('/cong-viec', [TaskController::class, 'index'])->name('tasks.index');
    Route::post('/cong-viec', [TaskController::class, 'store'])->name('tasks.store');
    Route::post('/cong-viec/bo-qua-qua-han', [TaskController::class, 'skipOverdue'])->name('tasks.skip-overdue');
    Route::post('/cong-viec/{task}/hoan-thanh', [TaskController::class, 'complete'])->whereNumber('task')->name('tasks.complete');
    Route::post('/cong-viec/{task}/bo-qua', [TaskController::class, 'skip'])->whereNumber('task')->name('tasks.skip');
    Route::post('/cong-viec/{task}/mo-lai', [TaskController::class, 'reopen'])->whereNumber('task')->name('tasks.reopen');
    Route::delete('/cong-viec/{task}', [TaskController::class, 'destroy'])->whereNumber('task')->name('tasks.destroy');

    // Hồ sơ trẻ (xem)
    Route::get('/tre', [ChildController::class, 'index'])->name('children.index');
    Route::get('/tre/{child}', [ChildController::class, 'show'])->whereNumber('child')->name('children.show');

    // Sổ nhật ký
    Route::get('/nhat-ky', [DiaryController::class, 'index'])->name('diary.index');
    Route::get('/nhat-ky/ghi', [DiaryController::class, 'create'])->name('diary.create');
    Route::get('/nhat-ky/bao-cao-ngay', [DiaryController::class, 'report'])->name('diary.report');
    Route::post('/nhat-ky', [DiaryController::class, 'store'])->name('diary.store');
    Route::delete('/nhat-ky/{entry}', [DiaryController::class, 'destroy'])->whereNumber('entry')->name('diary.destroy');

    // Thực đơn
    Route::get('/thuc-don', [MenuController::class, 'index'])->name('menus.index');
    Route::put('/thuc-don', [MenuController::class, 'update'])->name('menus.update');
    Route::post('/thuc-don/chep-tuan-truoc', [MenuController::class, 'copyPrevious'])->name('menus.copy');

    // Kho (xem)
    Route::get('/kho', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('/kho/{item}/lich-su', [InventoryController::class, 'history'])->whereNumber('item')->name('inventory.history');

    // Hồ sơ cá nhân
    Route::get('/ho-so', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/ho-so', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/ho-so/mat-khau', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::delete('/ho-so', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ===== CHỈ CHỦ NHÓM =====
Route::middleware(['auth', 'role:owner'])->group(function () {
    // Hồ sơ trẻ: thêm/sửa/chuyển lớp/nghỉ học/xoá + link phụ huynh
    Route::get('/tre/them', [ChildController::class, 'create'])->name('children.create');
    Route::post('/tre', [ChildController::class, 'store'])->name('children.store');
    Route::get('/tre/{child}/sua', [ChildController::class, 'edit'])->whereNumber('child')->name('children.edit');
    Route::put('/tre/{child}', [ChildController::class, 'update'])->whereNumber('child')->name('children.update');
    Route::post('/tre/{child}/chuyen-lop', [ChildController::class, 'move'])->whereNumber('child')->name('children.move');
    Route::post('/tre/{child}/nghi-hoc', [ChildController::class, 'leave'])->whereNumber('child')->name('children.leave');
    Route::post('/tre/{child}/di-hoc-lai', [ChildController::class, 'rejoin'])->whereNumber('child')->name('children.rejoin');
    Route::post('/tre/{child}/link-phu-huynh', [ChildController::class, 'shareLink'])->whereNumber('child')->name('children.share');
    Route::delete('/tre/{child}/link-phu-huynh', [ChildController::class, 'unshareLink'])->whereNumber('child')->name('children.unshare');
    Route::delete('/tre/{child}', [ChildController::class, 'destroy'])->whereNumber('child')->name('children.destroy');

    // Kho: nhập / sửa / kiểm kê
    Route::post('/kho', [InventoryController::class, 'store'])->name('inventory.store');
    Route::put('/kho/{item}', [InventoryController::class, 'update'])->whereNumber('item')->name('inventory.update');
    Route::delete('/kho/{item}', [InventoryController::class, 'destroy'])->whereNumber('item')->name('inventory.destroy');
    Route::post('/kho/{item}/khoi-phuc', [InventoryController::class, 'restore'])->whereNumber('item')->name('inventory.restore');
    Route::post('/kho/{item}/nhap', [InventoryController::class, 'stockIn'])->whereNumber('item')->name('inventory.stock-in');
    Route::post('/kho/{item}/kiem-ke', [InventoryController::class, 'adjust'])->whereNumber('item')->name('inventory.adjust');

    Route::prefix('quan-ly')->group(function () {
        // Lớp / nhóm tuổi
        Route::get('/lop', [ClassroomController::class, 'index'])->name('classrooms.index');
        Route::post('/lop/them-nhanh', [ClassroomController::class, 'quickAdd'])->name('classrooms.quick-add');
        Route::post('/lop', [ClassroomController::class, 'store'])->name('classrooms.store');
        Route::put('/lop/{classroom}', [ClassroomController::class, 'update'])->whereNumber('classroom')->name('classrooms.update');
        Route::delete('/lop/{classroom}', [ClassroomController::class, 'destroy'])->whereNumber('classroom')->name('classrooms.destroy');

        // Công việc định kỳ
        Route::get('/lich-dinh-ky', [RoutineController::class, 'index'])->name('routines.index');
        Route::get('/lich-dinh-ky/tao', [RoutineController::class, 'create'])->name('routines.create');
        Route::post('/lich-dinh-ky', [RoutineController::class, 'store'])->name('routines.store');
        Route::get('/lich-dinh-ky/{template}/sua', [RoutineController::class, 'edit'])->whereNumber('template')->name('routines.edit');
        Route::put('/lich-dinh-ky/{template}', [RoutineController::class, 'update'])->whereNumber('template')->name('routines.update');
        Route::post('/lich-dinh-ky/{template}/bat-tat', [RoutineController::class, 'toggle'])->whereNumber('template')->name('routines.toggle');
        Route::delete('/lich-dinh-ky/{template}', [RoutineController::class, 'destroy'])->whereNumber('template')->name('routines.destroy');

        // Học phí
        Route::get('/hoc-phi', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::post('/hoc-phi/tao-phieu', [InvoiceController::class, 'generate'])->name('invoices.generate');
        Route::get('/hoc-phi/{invoice}', [InvoiceController::class, 'show'])->whereNumber('invoice')->name('invoices.show');
        Route::put('/hoc-phi/{invoice}', [InvoiceController::class, 'update'])->whereNumber('invoice')->name('invoices.update');
        Route::delete('/hoc-phi/{invoice}', [InvoiceController::class, 'destroy'])->whereNumber('invoice')->name('invoices.destroy');
        Route::post('/hoc-phi/{invoice}/tinh-lai', [InvoiceController::class, 'recalculate'])->whereNumber('invoice')->name('invoices.recalculate');
        Route::post('/hoc-phi/{invoice}/thu-tien', [InvoiceController::class, 'pay'])->whereNumber('invoice')->name('invoices.pay');
        Route::delete('/hoc-phi/lan-thu/{payment}', [InvoiceController::class, 'destroyPayment'])->whereNumber('payment')->name('payments.destroy');

        // Chi phí
        Route::get('/chi-phi', [ExpenseController::class, 'index'])->name('expenses.index');
        Route::post('/chi-phi', [ExpenseController::class, 'store'])->name('expenses.store');
        Route::delete('/chi-phi/{expense}', [ExpenseController::class, 'destroy'])->whereNumber('expense')->name('expenses.destroy');

        // Báo cáo thu/chi
        Route::get('/bao-cao', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/bao-cao/cong-no-csv', [ReportController::class, 'exportDebts'])->name('reports.debts');

        // Cô giáo / bảo mẫu
        Route::get('/co-giao', [StaffController::class, 'index'])->name('staff.index');
        Route::post('/co-giao', [StaffController::class, 'store'])->name('staff.store');
        Route::post('/co-giao/{user}/kich-hoat', [StaffController::class, 'toggleActive'])->whereNumber('user')->name('staff.toggle');
        Route::post('/co-giao/{user}/doi-mat-khau', [StaffController::class, 'resetPassword'])->whereNumber('user')->name('staff.reset-password');
        Route::delete('/co-giao/{user}', [StaffController::class, 'remove'])->whereNumber('user')->name('staff.remove');

        // Cài đặt
        Route::get('/cai-dat', [SettingsController::class, 'index'])->name('settings.index');
        Route::put('/cai-dat/nhom-tre', [SettingsController::class, 'updateCenter'])->name('settings.center');
        Route::put('/cai-dat/hoc-phi', [SettingsController::class, 'updateBilling'])->name('settings.billing');
        Route::put('/cai-dat/giao-dien', [SettingsController::class, 'updateAppearance'])->name('settings.appearance');
        Route::get('/cai-dat/xuat-du-lieu', [DataExportController::class, 'export'])->name('settings.data-export');

        // Nhiều cơ sở
        Route::get('/cac-co-so', [CenterController::class, 'index'])->name('centers.index');
        Route::post('/cac-co-so', [CenterController::class, 'store'])->name('centers.store');
        Route::put('/cac-co-so/{center}', [CenterController::class, 'update'])->whereNumber('center')->name('centers.update');
        Route::post('/cac-co-so/{center}/chuyen', [CenterController::class, 'switch'])->whereNumber('center')->name('centers.switch');
    });
});
