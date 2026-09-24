<?php

use Core\Router;
use Controllers\AuthController;
use Controllers\DashboardController;
use Controllers\UserController;
use Controllers\ProfileController;
use Controllers\SettingsController;
use Controllers\ServicesController;
use Controllers\ServicePlacesController;
use Controllers\UsersController;
use Controllers\InviteController;
use Controllers\VerifyController;
use Controllers\PasswordController;
use Controllers\VacationsController;
use Controllers\AttendanceController;
use Controllers\TasksController;
use Controllers\ExportsController;
use Controllers\AdminSystemController;
use Controllers\WhatsNewController;
use Middleware\AuthMiddleware;
use Middleware\GuestMiddleware;

$router = new Router();

$router->get('/admin/system', [AdminSystemController::class, 'index']);
$router->post('/admin/system/run-migrations', [AdminSystemController::class, 'runMigrations']);
$router->post('/admin/system/install-update', [AdminSystemController::class, 'installUpdate']);

// ---------------- licence / disclaimer ---------------------------
$router->get('/license', [Controllers\OpenSourceController::class, 'license']);
$router->get('/disclaimer', [Controllers\OpenSourceController::class, 'disclaimer']);

// ------------------ zapomenuté heslo + obnova --------------
$router->get('/forgot-password', [PasswordController::class, 'forgotForm'], [GuestMiddleware::class]);
$router->post('/forgot-password', [PasswordController::class, 'sendReset'], [GuestMiddleware::class]);

$router->get('/reset-password/{token}', [PasswordController::class, 'resetForm'], [GuestMiddleware::class]);
$router->post('/reset-password/{token}', [PasswordController::class, 'resetStore'], [GuestMiddleware::class]);

// ----------------- Stránky uživatelé + vytváření hesla -------
$router->get('/users', [UsersController::class, 'index'], [AuthMiddleware::class]);
$router->post('/users/invite', [UsersController::class, 'invite'], [AuthMiddleware::class]);
$router->get('/users/{id}', [UsersController::class, 'show'], [AuthMiddleware::class]);

// veřejný link z emailu (nastavení hesla)
$router->get('/invite/{token}', [InviteController::class, 'show'], [GuestMiddleware::class]);
$router->post('/invite/{token}', [InviteController::class, 'store'], [GuestMiddleware::class]);

// ------------- ověření emailu ------------------
$router->get('/verify/{token}', [VerifyController::class, 'verify'], [GuestMiddleware::class]);

// ------------------ detail uživatele ----------
$router->post('/users/{id}/update', [UsersController::class, 'update'], [AuthMiddleware::class]);
$router->post('/users/{id}/extra',  [UsersController::class, 'updateExtra'], [AuthMiddleware::class]);

$router->post('/users/{id}/delete', [UsersController::class, 'delete'], [AuthMiddleware::class]);

// ---------- AUTH ----------
$router->get('/', [AuthController::class, 'loginForm'], [GuestMiddleware::class]);
$router->post('/login', [AuthController::class, 'login'], [GuestMiddleware::class]);
$router->get('/logout', [AuthController::class, 'logout']);

// ---------- DASHBOARD ----------
$router->get('/dashboard', [DashboardController::class, 'index'], [AuthMiddleware::class]);

$router->post('/whats-new/mark-seen', [WhatsNewController::class, 'markSeen']);

$router->post('/dashboard/layout/save', [DashboardController::class, 'saveLayout']);
$router->post('/dashboard/layout/widget-enabled', [DashboardController::class, 'setWidgetEnabled']);
$router->post('/dashboard/layout/reset', [DashboardController::class, 'resetLayout']);

$router->post('/dashboard/attendance-today', [Controllers\DashboardController::class, 'saveTodayAttendance']);

$router->post('/change-theme', [DashboardController::class, 'changeTheme'], [AuthMiddleware::class]);

$router->get('/profile', [ProfileController::class, 'index'], [AuthMiddleware::class]);

$router->get('/profile', [ProfileController::class, 'index'], [AuthMiddleware::class]);
$router->post('/profile/update', [ProfileController::class, 'updateProfile'], [AuthMiddleware::class]);
$router->post('/profile/avatar', [ProfileController::class, 'uploadAvatar'], [AuthMiddleware::class]);
$router->post('/profile/password', [ProfileController::class, 'changePassword'], [AuthMiddleware::class]);


// ------- úkoly ----------
$router->get('/tasks', [TasksController::class, 'index']);
$router->get('/tasks/data', [TasksController::class, 'data']);

$router->post('/tasks/create', [TasksController::class, 'create']);
$router->post('/tasks/update', [TasksController::class, 'update']);
$router->post('/tasks/toggle', [TasksController::class, 'toggle']);
$router->post('/tasks/delete', [TasksController::class, 'delete']);

$router->post('/tasks/group/create', [TasksController::class, 'createGroup']);
$router->post('/tasks/group/update', [TasksController::class, 'updateGroup']);
$router->post('/tasks/group/delete', [TasksController::class, 'deleteGroup']);

$router->post('/tasks/group/item/create', [TasksController::class, 'createGroupItem']);
$router->post('/tasks/group/item/toggle', [TasksController::class, 'toggleGroupItem']);

$router->post('/tasks/group/items/delete-completed', [TasksController::class, 'deleteCompletedGroupItems']);

$router->get('/tasks/dashboard-data', [TasksController::class, 'dashboardData']);

$router->get('/tasks/dashboard-groups-data', [TasksController::class, 'dashboardGroupsData']);

/*
|--------------------------------------------------------------------------
| HLÁŠENÍ ODPADŮ
|--------------------------------------------------------------------------
*/

$router->get('/waste-reports', [Controllers\WasteReportsController::class, 'index']);

$router->post('/waste-reports/create', [Controllers\WasteReportsController::class, 'createCollection']);

$router->post('/waste-reports/update/{id}', [Controllers\WasteReportsController::class, 'updateCollection']);

$router->post('/waste-reports/delete/{id}', [Controllers\WasteReportsController::class, 'deleteCollection']);

$router->get('/waste-reports/export', [Controllers\WasteReportsController::class, 'exportPdf']);

// ---------- POZVÁNKY ----------
//$router->get('/invite/{token}', [UserController::class, 'acceptInvitationForm'], [GuestMiddleware::class]);
//$router->post('/invite/{token}', [UserController::class, 'acceptInvitation'], [GuestMiddleware::class]);

// ------------ docházka -----------------

$router->get('/attendance', [AttendanceController::class, 'index'], [AuthMiddleware::class]);
$router->post('/attendance/day', [AttendanceController::class, 'saveDay'], [AuthMiddleware::class]);

// ----------- Nastavení ---------
$router->get('/settings', [SettingsController::class, 'index'], [AuthMiddleware::class]);
$router->post('/settings', [SettingsController::class, 'update'], [AuthMiddleware::class]);

$router->post('/settings/services/company', [SettingsController::class, 'saveServicesCompanySettings'], [AuthMiddleware::class]);

$router->post('/settings/attendance', [SettingsController::class, 'saveAttendanceSettings'], [AuthMiddleware::class]);

$router->post('/settings/waste-reports/company', [SettingsController::class, 'saveWasteReportsCompany'], [AuthMiddleware::class]);

$router->post('/settings/exports/company', [SettingsController::class, 'updateExportsCompany']);

$router->post('/settings/payrolls', [SettingsController::class, 'updatePayrolls']);


$router->post('/settings/economic-indicators', [SettingsController::class, 'updateEconomicIndicators']);
$router->post('/settings/economic-indicators/item/create', [SettingsController::class, 'createEconomicIndicatorDefinition']);
$router->post('/settings/economic-indicators/item/update', [SettingsController::class, 'updateEconomicIndicatorDefinition']);
$router->post('/settings/economic-indicators/item/delete', [SettingsController::class, 'deleteEconomicIndicatorDefinition']);


$router->post('/settings/waste-reports/places/create', [SettingsController::class, 'createWasteReportPlace'], [AuthMiddleware::class]);
$router->post('/settings/waste-reports/places/update/{id}', [SettingsController::class, 'updateWasteReportPlace'], [AuthMiddleware::class]);
$router->post('/settings/waste-reports/places/delete/{id}', [SettingsController::class, 'deleteWasteReportPlace'], [AuthMiddleware::class]);

$router->post('/settings/temperatures/places/create', [Controllers\SettingsController::class, 'createTemperaturePlace']);
$router->post('/settings/temperatures/places/update/{id}', [Controllers\SettingsController::class, 'updateTemperaturePlace']);
$router->post('/settings/temperatures/places/delete/{id}', [Controllers\SettingsController::class, 'deleteTemperaturePlace']);

$router->post('/settings/sterilization-drying/places/create', [Controllers\SettingsController::class, 'createSterilizationPlace']);
$router->post('/settings/sterilization-drying/places/update/{id}', [Controllers\SettingsController::class, 'updateSterilizationPlace']);
$router->post('/settings/sterilization-drying/places/delete/{id}', [Controllers\SettingsController::class, 'deleteSterilizationPlace']);


// ---------------- výplaty -----------------
$router->get('/payrolls', [\Controllers\PayrollsController::class, 'index']);
$router->get('/payrolls/create', [\Controllers\PayrollsController::class, 'create']);
$router->post('/payrolls/create', [\Controllers\PayrollsController::class, 'store']);
$router->post('/payrolls/{id}/allow-attendance-edit', [\Controllers\PayrollsController::class, 'allowAttendanceEdit']);
$router->post('/payrolls/{id}/recalculate-item', [\Controllers\PayrollsController::class, 'recalculateItem']);
$router->post('/payrolls/{id}/update-item', [\Controllers\PayrollsController::class, 'updateItem']);
$router->post('/payrolls/{id}/approve', [\Controllers\PayrollsController::class, 'approve']);
$router->post('/payrolls/{id}/reopen', [\Controllers\PayrollsController::class, 'reopen']);
$router->get('/payrolls/{id}', [\Controllers\PayrollsController::class, 'show']);



// ------------- ekonomické ukazatele -----------------
$router->get('/economic-indicators', [\Controllers\EconomicIndicatorsController::class, 'index']);

$router->get('/economic-indicators/chart-data', [\Controllers\EconomicIndicatorsController::class, 'chartData']);

$router->post('/economic-indicators/store', [\Controllers\EconomicIndicatorsController::class, 'store']);
$router->post('/economic-indicators/update', [\Controllers\EconomicIndicatorsController::class, 'update']);
$router->post('/economic-indicators/delete', [\Controllers\EconomicIndicatorsController::class, 'delete']);

// ------------ exporty ---------------
$router->get('/exports', [ExportsController::class, 'index']);

$router->post('/exports/meal-vouchers/preview', [ExportsController::class, 'mealVouchersPreview']);
$router->get('/exports/temperatures/print', [ExportsController::class, 'temperaturesPrint']);

// ------------- teploty -------------
$router->get('/temperatures', [Controllers\TemperaturesController::class, 'index']);
$router->post('/temperatures/save', [Controllers\TemperaturesController::class, 'save']);
$router->post('/temperatures/save-inline', [Controllers\TemperaturesController::class, 'saveInline']);

$router->get('/temperatures/dashboard-data', [Controllers\TemperaturesController::class, 'dashboardData']);

// --------------- sterilizace a sušení -------------
$router->get('/sterilization-drying', [Controllers\SterilizationDryingController::class, 'index']);
$router->post('/sterilization-drying/create', [Controllers\SterilizationDryingController::class, 'create']);
$router->post('/sterilization-drying/update/{id}', [Controllers\SterilizationDryingController::class, 'update']);
$router->post('/sterilization-drying/delete/{id}', [Controllers\SterilizationDryingController::class, 'delete']);

// ------------ Služby -----------
$router->get('/services', [ServicesController::class, 'index'], [AuthMiddleware::class]);

$router->post('/services/assign', [ServicesController::class, 'assign'], [AuthMiddleware::class]);
$router->post('/services/unassign', [ServicesController::class, 'unassign'], [AuthMiddleware::class]);

$router->post('/services/note', [ServicesController::class, 'saveNote'], [AuthMiddleware::class]);

$router->post('/services/day-note', [ServicesController::class, 'saveDayNote'], [AuthMiddleware::class]);

// --------------- Nastavení / služby -----------------
$router->post('/settings/services/places/create', [ServicePlacesController::class, 'create'], [AuthMiddleware::class]);
$router->post('/settings/services/places/delete/{id}', [ServicePlacesController::class, 'delete'], [AuthMiddleware::class]);
$router->post('/settings/services/places/update/{id}', [ServicePlacesController::class, 'update'], [AuthMiddleware::class]);

// -------------- Dovolené --------------------
$router->get('/vacations', [VacationsController::class, 'index'], [AuthMiddleware::class]);
$router->post('/vacations', [VacationsController::class, 'store'], [AuthMiddleware::class]);

$router->get('/vacations/all', [VacationsController::class, 'all'], [AuthMiddleware::class]);

$router->post('/vacations/{id}/delete', [VacationsController::class, 'delete']);

$router->get('/vacations/{id}', [VacationsController::class, 'show'], [AuthMiddleware::class]);
$router->post('/vacations/{id}', [VacationsController::class, 'update'], [AuthMiddleware::class]);
