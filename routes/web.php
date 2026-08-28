<?php

use App\Http\Controllers\Web\AcademicModuleController;
use App\Http\Controllers\Web\AcademicModuleTeacherController;
use App\Http\Controllers\Web\ActiveCompanyController;
use App\Http\Controllers\Web\AdminDataTableController;
use App\Http\Controllers\Web\AreaController;
use App\Http\Controllers\Web\AuditController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\Billing\CafcContingencyController;
use App\Http\Controllers\Web\Billing\CafcRangeController;
use App\Http\Controllers\Web\Billing\ContingencyDashboardController;
use App\Http\Controllers\Web\Billing\FiscalArtifactController;
use App\Http\Controllers\Web\Billing\InvoiceController;
use App\Http\Controllers\Web\Billing\InvoiceIssueController;
use App\Http\Controllers\Web\Billing\InvoicePrintSettingController;
use App\Http\Controllers\Web\Billing\InvoiceTestBatchController;
use App\Http\Controllers\Web\Billing\ManualCafcInvoiceController;
use App\Http\Controllers\Web\Billing\SignificantEventController;
use App\Http\Controllers\Web\CashRegisterController;
use App\Http\Controllers\Web\CampusController;
use App\Http\Controllers\Web\CompanyController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\DatabaseBackupController;
use App\Http\Controllers\Web\MyAccountController;
use App\Http\Controllers\Web\NotificationController;
use App\Http\Controllers\Web\Parameters\CustomerController;
use App\Http\Controllers\Web\Parameters\CommercialOriginController;
use App\Http\Controllers\Web\Parameters\PlanController;
use App\Http\Controllers\Web\Parameters\ProductCategoryController;
use App\Http\Controllers\Web\Parameters\ProductController;
use App\Http\Controllers\Web\Parameters\ProgramController;
use App\Http\Controllers\Web\Parameters\ProgramLevelController;
use App\Http\Controllers\Web\Parameters\SinAuthorizationController;
use App\Http\Controllers\Web\PermissionController;
use App\Http\Controllers\Web\PersonnelController;
use App\Http\Controllers\Web\PositionController;
use App\Http\Controllers\Web\Rectorate\AccountStatementController;
use App\Http\Controllers\Web\Rectorate\NewApplicationController;
use App\Http\Controllers\Web\RoleController;
use App\Http\Controllers\Web\Reports\EnrollmentReportController;
use App\Http\Controllers\Web\SiatBranchController;
use App\Http\Controllers\Web\SiatCatalogController;
use App\Http\Controllers\Web\SiatCommunicationController;
use App\Http\Controllers\Web\SiatCuisController;
use App\Http\Controllers\Web\SiatWsdlServiceController;
use App\Http\Controllers\Web\SinApiTokenController;
use App\Http\Controllers\Web\StudentController;
use App\Http\Controllers\Web\StudentKardexController;
use App\Http\Controllers\Web\StudentModuleAssignmentController;
use App\Http\Controllers\Web\TeacherModuleController;
use App\Http\Controllers\Web\TeacherModuleResultController;
use App\Http\Controllers\Web\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->name('login.store');
});

Route::middleware(['auth', 'active_account'])->group(function (): void {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    Route::put('empresa-activa', [ActiveCompanyController::class, 'update'])->name('active-company.update');
    Route::get('mi-cuenta', [MyAccountController::class, 'edit'])->name('account.edit');
    Route::put('mi-cuenta/contrasena', [MyAccountController::class, 'updatePassword'])->name('account.password.update');

    Route::get('/', DashboardController::class)->name('dashboard');
    Route::prefix('academic')->name('academic.')->middleware('company_user')->group(function (): void {
        Route::get('modules', [AcademicModuleController::class, 'index'])->middleware('permission:academic-modules.view')->name('modules.index');
        Route::get('modules/create', [AcademicModuleController::class, 'create'])->middleware('permission:academic-modules.manage')->name('modules.create');
        Route::post('modules', [AcademicModuleController::class, 'store'])->middleware('permission:academic-modules.manage')->name('modules.store');
        Route::get('modules/{module}/edit', [AcademicModuleController::class, 'edit'])->whereNumber('module')->middleware('permission:academic-modules.manage')->name('modules.edit');
        Route::put('modules/{module}', [AcademicModuleController::class, 'update'])->whereNumber('module')->middleware('permission:academic-modules.manage')->name('modules.update');
        Route::delete('modules/{module}', [AcademicModuleController::class, 'destroy'])->whereNumber('module')->middleware('permission:academic-modules.manage')->name('modules.destroy');
        Route::get('modules/{module}/teacher', [AcademicModuleTeacherController::class, 'edit'])->whereNumber('module')->middleware('permission:academic-modules.manage')->name('modules.teacher.edit');
        Route::put('modules/{module}/teacher', [AcademicModuleTeacherController::class, 'update'])->whereNumber('module')->middleware('permission:academic-modules.manage')->name('modules.teacher.update');
    });
    Route::prefix('students')->name('students.')->middleware('company_user')->group(function (): void {
        Route::get('/', [StudentController::class, 'index'])->middleware('permission:students.view')->name('index');
        Route::get('{student}/kardex', [StudentKardexController::class, 'show'])->whereNumber('student')->middleware('permission:students.view')->name('kardex.show');
        Route::get('{student}/kardex/pdf', [StudentKardexController::class, 'print'])->whereNumber('student')->middleware('permission:students.view')->name('kardex.pdf');
        Route::get('{student}/modules/create', [StudentModuleAssignmentController::class, 'create'])->whereNumber('student')->middleware('permission:students.manage')->name('modules.create');
        Route::post('{student}/modules', [StudentModuleAssignmentController::class, 'store'])->whereNumber('student')->middleware('permission:students.manage')->name('modules.store');
    });
    Route::prefix('reports')->name('reports.')->middleware(['company_user', 'permission:enrollment-reports.view'])->group(function (): void {
        Route::get('enrollments', [EnrollmentReportController::class, 'index'])->name('enrollments.index');
        Route::get('enrollments/pdf', [EnrollmentReportController::class, 'print'])->name('enrollments.pdf');
    });
    Route::prefix('teacher')->name('teacher.')->middleware('company_user')->group(function (): void {
        Route::get('modules', [TeacherModuleController::class, 'index'])->middleware('permission:teaching.view')->name('modules.index');
        Route::post('modules/{module}/sessions', [TeacherModuleController::class, 'start'])->whereNumber('module')->middleware('permission:teaching.manage')->name('modules.sessions.start');
        Route::get('modules/{module}/sessions/{session}/attendance', [TeacherModuleController::class, 'editAttendance'])->whereNumber(['module', 'session'])->middleware('permission:teaching.manage')->name('modules.attendance.edit');
        Route::put('modules/{module}/sessions/{session}/attendance', [TeacherModuleController::class, 'updateAttendance'])->whereNumber(['module', 'session'])->middleware('permission:teaching.manage')->name('modules.attendance.update');
        Route::get('modules/{module}/results', [TeacherModuleResultController::class, 'edit'])->whereNumber('module')->middleware('permission:teaching.manage')->name('modules.results.edit');
        Route::put('modules/{module}/results', [TeacherModuleResultController::class, 'update'])->whereNumber('module')->middleware('permission:teaching.manage')->name('modules.results.update');
    });
    Route::post('notifications/read-all', [NotificationController::class, 'readAll'])
        ->name('notifications.read-all');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'read'])
        ->whereUuid('notification')->name('notifications.read');
    Route::get('audits', [AuditController::class, 'index'])->middleware('permission:audits.view')->name('audits.index');
    Route::get('audits/{audit}', [AuditController::class, 'show'])->middleware('permission:audits.view')->name('audits.show');
    Route::prefix('backups')->name('backups.')->group(function (): void {
        Route::get('/', [DatabaseBackupController::class, 'index'])->middleware('permission:backups.view')->name('index');
        Route::post('/', [DatabaseBackupController::class, 'store'])->middleware('permission:backups.create')->name('store');
        Route::post('upload-restore', [DatabaseBackupController::class, 'uploadAndRestore'])
            ->middleware(['permission:backups.restore', 'throttle:3,10'])->name('upload-restore');
        Route::get('{backup}/download', [DatabaseBackupController::class, 'download'])
            ->where('backup', 'facturacion-[0-9]{8}-[0-9]{6}-[a-f0-9]{6}\\.sql\\.gz')
            ->middleware('permission:backups.download')->name('download');
        Route::post('{backup}/restore', [DatabaseBackupController::class, 'restore'])
            ->where('backup', 'facturacion-[0-9]{8}-[0-9]{6}-[a-f0-9]{6}\\.sql\\.gz')
            ->middleware(['permission:backups.restore', 'throttle:3,10'])->name('restore');
        Route::delete('{backup}', [DatabaseBackupController::class, 'destroy'])
            ->where('backup', 'facturacion-[0-9]{8}-[0-9]{6}-[a-f0-9]{6}\\.sql\\.gz')
            ->middleware('permission:backups.delete')->name('destroy');
    });
    Route::prefix('companies')->name('companies.')->group(function (): void {
        Route::get('/', [CompanyController::class, 'index'])->middleware('permission:companies.view')->name('index');
        Route::get('create', [CompanyController::class, 'create'])->middleware('permission:companies.create')->name('create');
        Route::post('/', [CompanyController::class, 'store'])->middleware('permission:companies.create')->name('store');
        Route::get('{company}', [CompanyController::class, 'show'])->middleware('permission:companies.view')->name('show');
        Route::get('{company}/edit', [CompanyController::class, 'edit'])->middleware('permission:companies.update')->name('edit');
        Route::put('{company}', [CompanyController::class, 'update'])->middleware('permission:companies.update')->name('update');
        Route::delete('{company}', [CompanyController::class, 'destroy'])->middleware('permission:companies.delete')->name('destroy');
    });
    Route::prefix('cash-registers')
        ->name('cash-registers.')
        ->middleware('company_user')
        ->group(function (): void {
            Route::get('/', [CashRegisterController::class, 'index'])
                ->middleware('permission:cash-registers.view')
                ->name('index');
            Route::get('history', [CashRegisterController::class, 'history'])
                ->middleware('permission:cash-registers.view')
                ->name('history');
            Route::get('history/{cashRegister}', [CashRegisterController::class, 'show'])
                ->whereNumber('cashRegister')
                ->middleware('permission:cash-registers.view')
                ->name('show');
            Route::post('/', [CashRegisterController::class, 'store'])
                ->middleware('permission:cash-registers.open')
                ->name('store');
            Route::patch('{cashRegister}/close', [CashRegisterController::class, 'close'])
                ->whereNumber('cashRegister')
                ->middleware('permission:cash-registers.close')
                ->name('close');
        });
    Route::prefix('facturacion/contingencias')
        ->name('billing.contingencies.')
        ->group(function (): void {
            Route::get('/', [ContingencyDashboardController::class, 'index'])
                ->middleware('permission:contingencies.view')->name('index');
            Route::get('eventos/{event}', [ContingencyDashboardController::class, 'event'])
                ->whereNumber('event')->middleware('permission:contingencies.events.view')->name('events.show');
            Route::get('respuesta/{type}/{id}', [ContingencyDashboardController::class, 'technical'])
                ->whereIn('type', ['invoice', 'package', 'event'])->whereNumber('id')
                ->middleware('permission:contingencies.technical.view')->name('technical.show');
            Route::post('comunicacion/consultar', [ContingencyDashboardController::class, 'verifyCommunication'])
                ->middleware('permission:contingencies.communication.check')->name('communication.check');
            Route::post('eventos/{event}/reintentar-registro', [ContingencyDashboardController::class, 'retryEvent'])
                ->whereNumber('event')->middleware('permission:contingencies.events.retry')->name('events.retry');
            Route::post('eventos/{event}/regularizar', [ContingencyDashboardController::class, 'regularizeEvent'])
                ->whereNumber('event')->middleware('permission:contingencies.events.retry')->name('events.regularize');
            Route::post('eventos/{event}/registrar', [ContingencyDashboardController::class, 'registerEvent'])
                ->whereNumber('event')->middleware('permission:contingencies.events.retry')->name('events.register');
            Route::post('eventos/{event}/generar-paquetes', [ContingencyDashboardController::class, 'buildPackages'])
                ->whereNumber('event')->middleware('permission:contingencies.packages.build')->name('packages.build');
            Route::post('paquetes/{package}/reintentar-envio', [ContingencyDashboardController::class, 'sendPackage'])
                ->whereNumber('package')->middleware('permission:contingencies.packages.send')->name('packages.send');
            Route::post('paquetes/{package}/consultar-validacion', [ContingencyDashboardController::class, 'validatePackage'])
                ->whereNumber('package')->middleware('permission:contingencies.packages.validate')->name('packages.validate');
            Route::get('facturas/{invoice}/xml', [FiscalArtifactController::class, 'xml'])
                ->whereNumber('invoice')->middleware('permission:contingencies.artifacts.download')->name('invoices.xml');
            Route::get('facturas/{invoice}/representacion', [FiscalArtifactController::class, 'pdf'])
                ->whereNumber('invoice')->middleware('permission:contingencies.artifacts.download')->name('invoices.pdf');
        });
    Route::prefix('facturacion')
        ->name('billing.')
        ->middleware('company_user')
        ->group(function (): void {
            Route::get('facturas', [InvoiceController::class, 'index'])
                ->middleware('permission:invoices.view')
                ->name('invoices.index');
            Route::get('facturas/{invoice}/imprimir', [InvoiceController::class, 'print'])
                ->whereNumber('invoice')
                ->middleware('permission:invoices.view')
                ->name('invoices.print');
            Route::get('facturas/{invoice}/xml', [FiscalArtifactController::class, 'viewXml'])
                ->whereNumber('invoice')
                ->middleware('permission:invoices.view')
                ->name('invoices.xml');
            Route::get('facturas/{invoice}/anular', [InvoiceController::class, 'cancelForm'])
                ->whereNumber('invoice')->middleware('permission:invoices.cancel')->name('invoices.cancel.form');
            Route::post('facturas/{invoice}/anular', [InvoiceController::class, 'cancel'])
                ->whereNumber('invoice')->middleware('permission:invoices.cancel')->name('invoices.cancel');
            Route::get('facturas/{invoice}/revertir-anulacion', [InvoiceController::class, 'reversalForm'])
                ->whereNumber('invoice')->middleware('permission:invoices.cancel')->name('invoices.reversal.form');
            Route::post('facturas/{invoice}/revertir-anulacion', [InvoiceController::class, 'reverseCancellation'])
                ->whereNumber('invoice')->middleware('permission:invoices.cancel')->name('invoices.reversal');
            Route::get('facturas/{invoice}/corregir-pago', [InvoiceController::class, 'correctPaymentForm'])
                ->whereNumber('invoice')->middleware('permission:invoices.issue')->name('invoices.payment.correct.form');
            Route::post('facturas/{invoice}/corregir-pago', [InvoiceController::class, 'correctPayment'])
                ->whereNumber('invoice')->middleware('permission:invoices.issue')->name('invoices.payment.correct');
            Route::post('facturas/{invoice}/reenviar', [InvoiceController::class, 'resendPendingOnline'])
                ->whereNumber('invoice')->middleware('permission:invoices.issue')->name('invoices.resend');
            Route::get('cafc', [CafcRangeController::class, 'index'])
                ->middleware('permission:cafc-ranges.view')->name('cafc-ranges.index');
            Route::post('cafc', [CafcRangeController::class, 'store'])
                ->middleware('permission:cafc-ranges.manage')->name('cafc-ranges.store');
            Route::delete('cafc/{cafcRange}', [CafcRangeController::class, 'destroy'])
                ->whereNumber('cafcRange')->middleware('permission:cafc-ranges.manage')->name('cafc-ranges.destroy');
            Route::get('contingencias-2', [CafcContingencyController::class, 'index'])
                ->middleware('permission:cafc-ranges.view')->name('cafc-contingencies.index');
            Route::post('contingencias-2', [CafcContingencyController::class, 'storeRange'])
                ->middleware('permission:cafc-ranges.manage')->name('cafc-contingencies.store');
            Route::get('contingencias-2/{cafcRange}', [CafcContingencyController::class, 'show'])
                ->whereNumber('cafcRange')->middleware('permission:manual-cafc.view')->name('cafc-contingencies.show');
            Route::patch('contingencias-2/{cafcRange}/codigo', [CafcContingencyController::class, 'updateCode'])
                ->whereNumber('cafcRange')->middleware('permission:cafc-ranges.manage')->name('cafc-contingencies.code.update');
            Route::post('contingencias-2/{cafcRange}/facturas', [CafcContingencyController::class, 'storeInvoice'])
                ->whereNumber('cafcRange')->middleware('permission:manual-cafc.use')->name('cafc-contingencies.invoices.store');
            Route::post('contingencias-2/{cafcRange}/finalizar', [CafcContingencyController::class, 'finalize'])
                ->whereNumber('cafcRange')->middleware('permission:manual-cafc.use')->name('cafc-contingencies.finalize');
            Route::get('manuales-cafc', [ManualCafcInvoiceController::class, 'index'])
                ->middleware('permission:manual-cafc.view')->name('manual-cafc.index');
            Route::post('manuales-cafc', [ManualCafcInvoiceController::class, 'store'])
                ->middleware('permission:manual-cafc.use')->name('manual-cafc.store');
            Route::get('manuales-cafc/{manualInvoice}/transcribir', [ManualCafcInvoiceController::class, 'edit'])
                ->whereNumber('manualInvoice')->middleware('permission:manual-cafc.transcribe')->name('manual-cafc.transcribe.edit');
            Route::put('manuales-cafc/{manualInvoice}/transcribir', [ManualCafcInvoiceController::class, 'update'])
                ->whereNumber('manualInvoice')->middleware('permission:manual-cafc.transcribe')->name('manual-cafc.transcribe.update');
            Route::post('manuales-cafc/{manualInvoice}/enviar', [ManualCafcInvoiceController::class, 'send'])
                ->whereNumber('manualInvoice')->middleware('permission:manual-cafc.transcribe')->name('manual-cafc.send');
            Route::get('configuracion/impresion', [InvoicePrintSettingController::class, 'edit'])
                ->middleware('permission:invoices.issue')
                ->name('invoice-print-settings.edit');
            Route::put('configuracion/impresion', [InvoicePrintSettingController::class, 'update'])
                ->middleware('permission:invoices.issue')
                ->name('invoice-print-settings.update');
            Route::get('emitir', [InvoiceIssueController::class, 'index'])
                ->middleware('permission:invoices.issue')
                ->name('invoices.issue.index');
            Route::get('pruebas', [InvoiceTestBatchController::class, 'index'])
                ->middleware('permission:invoice-tests.run')
                ->name('invoice-tests.index');
            Route::post('pruebas', [InvoiceTestBatchController::class, 'store'])
                ->middleware('permission:invoice-tests.run')
                ->name('invoice-tests.store');
            Route::post('pruebas/{batch}/anular', [InvoiceTestBatchController::class, 'cancel'])
                ->whereNumber('batch')->middleware('permission:invoice-tests.run')
                ->name('invoice-tests.cancel');
            Route::post('pruebas/{batch}/revertir-anulaciones', [InvoiceTestBatchController::class, 'reverse'])
                ->whereNumber('batch')->middleware('permission:invoice-tests.run')
                ->name('invoice-tests.reverse');
            Route::post('emitir/cufd/request', [InvoiceIssueController::class, 'requestCufd'])
                ->middleware('permission:invoices.issue')
                ->name('invoices.issue.cufd.request');
            Route::post('emitir/compra-venta', [InvoiceIssueController::class, 'issuePurchaseSale'])
                ->middleware('permission:invoices.issue')
                ->name('invoices.issue.purchase-sale.store');
            Route::get('facturas/{invoice}/evento-significativo', [SignificantEventController::class, 'create'])
                ->whereNumber('invoice')
                ->middleware('permission:invoices.issue')
                ->name('significant-events.create');
            Route::post('facturas/{invoice}/evento-significativo', [SignificantEventController::class, 'store'])
                ->whereNumber('invoice')
                ->middleware('permission:invoices.issue')
                ->name('significant-events.store');
            Route::get('eventos-significativos', [SignificantEventController::class, 'index'])
                ->middleware('permission:invoices.issue')
                ->name('significant-events.index');
            Route::get('eventos-significativos/registrar/{pointOfSale}', [SignificantEventController::class, 'createForPointOfSale'])
                ->whereNumber('pointOfSale')
                ->middleware('permission:invoices.issue')
                ->name('significant-events.point-of-sale.create');
            Route::post('eventos-significativos/registrar', [SignificantEventController::class, 'storeForPointOfSale'])
                ->middleware('permission:invoices.issue')
                ->name('significant-events.point-of-sale.store');
            Route::get('emitir/{documentSectorCode}', [InvoiceIssueController::class, 'show'])
                ->whereNumber('documentSectorCode')
                ->middleware('permission:invoices.issue')
                ->name('invoices.issue.show');
        });
    Route::prefix('api-token')
        ->name('sin-api-token.')
        ->middleware('company_user')
        ->group(function (): void {
            Route::get('/', [SinApiTokenController::class, 'index'])
                ->middleware('permission:sin-api-tokens.view')
                ->name('index');
            Route::post('/', [SinApiTokenController::class, 'store'])
                ->middleware('permission:sin-api-tokens.manage')
                ->name('store');
            Route::put('/', [SinApiTokenController::class, 'update'])
                ->middleware('permission:sin-api-tokens.manage')
                ->name('update');
        });
    Route::prefix('siat')
        ->name('siat.')
        ->middleware('company_user')
        ->group(function (): void {
            Route::get('wsdl-services', [SiatWsdlServiceController::class, 'index'])
                ->middleware('permission:sin-api-tokens.view')
                ->name('wsdl-services.index');
            Route::post('wsdl-services', [SiatWsdlServiceController::class, 'store'])
                ->middleware('permission:sin-api-tokens.manage')
                ->name('wsdl-services.store');
            Route::put('wsdl-services/{wsdlService}', [SiatWsdlServiceController::class, 'update'])
                ->whereNumber('wsdlService')
                ->middleware('permission:sin-api-tokens.manage')
                ->name('wsdl-services.update');
            Route::delete('wsdl-services/{wsdlService}', [SiatWsdlServiceController::class, 'destroy'])
                ->whereNumber('wsdlService')
                ->middleware('permission:sin-api-tokens.manage')
                ->name('wsdl-services.destroy');
            Route::get('communication', [SiatCommunicationController::class, 'index'])
                ->middleware('permission:siat-communication.view')
                ->name('communication.index');
            Route::post('communication/verify', [SiatCommunicationController::class, 'verify'])
                ->middleware('permission:siat-communication.verify')
                ->name('communication.verify');
            Route::get('cuis', [SiatCuisController::class, 'index'])
                ->middleware('permission:siat-cuis.view')
                ->name('cuis.index');
            Route::post('cuis/request', [SiatCuisController::class, 'request'])
                ->middleware('permission:siat-cuis.request')
                ->name('cuis.request');
            Route::post('cuis/import', [SiatCuisController::class, 'importExisting'])
                ->middleware('permission:siat-cuis.request')
                ->name('cuis.import');
            Route::get('catalogs', [SiatCatalogController::class, 'index'])
                ->middleware('permission:siat-catalogs.view')
                ->name('catalogs.index');
            Route::post('catalogs/sync-all', [SiatCatalogController::class, 'syncAll'])
                ->middleware('permission:siat-catalogs.sync')
                ->name('catalogs.sync-all');
            Route::get('catalogs/{catalog}', [SiatCatalogController::class, 'show'])
                ->middleware('permission:siat-catalogs.view')
                ->name('catalogs.show');
            Route::post('catalogs/{catalog}/sync', [SiatCatalogController::class, 'sync'])
                ->middleware('permission:siat-catalogs.sync')
                ->name('catalogs.sync');
            Route::patch('catalogs/{catalog}/items/status', [SiatCatalogController::class, 'updateItemsStatus'])
                ->middleware('permission:siat-catalogs.sync')
                ->name('catalogs.items.status');
            Route::patch('catalogs/{catalog}/items/{item}/status', [SiatCatalogController::class, 'updateItemStatus'])
                ->whereNumber('item')
                ->middleware('permission:siat-catalogs.sync')
                ->name('catalogs.items.update-status');
            Route::get('branches', [SiatBranchController::class, 'index'])
                ->middleware('permission:siat-branches.view')
                ->name('branches.index');
            Route::post('branches', [SiatBranchController::class, 'store'])
                ->middleware('permission:siat-branches.manage')
                ->name('branches.store');
            Route::post('branches/{branch}/points', [SiatBranchController::class, 'storePoint'])
                ->whereNumber('branch')
                ->middleware('permission:siat-branches.manage')
                ->name('branches.points.store');
            Route::post('branches/{branch}/points/synchronize', [SiatBranchController::class, 'synchronizePoints'])
                ->whereNumber('branch')
                ->middleware('permission:siat-branches.manage')
                ->name('branches.points.synchronize');
        });
    Route::prefix('parameters')
        ->name('parameters.')
        ->middleware('company_user')
        ->group(function (): void {
            Route::prefix('programs')->name('programs.')->group(function (): void {
                Route::get('/', [ProgramController::class, 'index'])->middleware('permission:programs.view')->name('index');
                Route::get('create', [ProgramController::class, 'create'])->middleware('permission:programs.create')->name('create');
                Route::post('/', [ProgramController::class, 'store'])->middleware('permission:programs.create')->name('store');
                Route::get('{program}/edit', [ProgramController::class, 'edit'])->whereNumber('program')->middleware('permission:programs.edit')->name('edit');
                Route::put('{program}', [ProgramController::class, 'update'])->whereNumber('program')->middleware('permission:programs.edit')->name('update');
                Route::get('{program}/levels', [ProgramLevelController::class, 'index'])->whereNumber('program')->middleware('permission:programs.edit')->name('levels.index');
                Route::post('{program}/levels', [ProgramLevelController::class, 'store'])->whereNumber('program')->middleware('permission:programs.edit')->name('levels.store');
                Route::put('{program}/levels/{level}', [ProgramLevelController::class, 'update'])->whereNumber(['program', 'level'])->middleware('permission:programs.edit')->name('levels.update');
                Route::delete('{program}/levels/{level}', [ProgramLevelController::class, 'destroy'])->whereNumber(['program', 'level'])->middleware('permission:programs.edit')->name('levels.destroy');
            });
            Route::prefix('plans')->name('plans.')->group(function (): void {
                Route::get('/', [PlanController::class, 'index'])->middleware('permission:plans.view')->name('index');
                Route::get('create', [PlanController::class, 'create'])->middleware('permission:plans.create')->name('create');
                Route::post('/', [PlanController::class, 'store'])->middleware('permission:plans.create')->name('store');
                Route::get('{plan}/edit', [PlanController::class, 'edit'])->whereNumber('plan')->middleware('permission:plans.edit')->name('edit');
                Route::put('{plan}', [PlanController::class, 'update'])->whereNumber('plan')->middleware('permission:plans.edit')->name('update');
            });
            Route::prefix('commercial-origins')->name('commercial-origins.')->group(function (): void {
                Route::get('/', [CommercialOriginController::class, 'index'])->middleware('permission:commercial-origins.view')->name('index');
                Route::get('create', [CommercialOriginController::class, 'create'])->middleware('permission:commercial-origins.create')->name('create');
                Route::post('/', [CommercialOriginController::class, 'store'])->middleware('permission:commercial-origins.create')->name('store');
                Route::get('{commercialOrigin}/edit', [CommercialOriginController::class, 'edit'])->whereNumber('commercialOrigin')->middleware('permission:commercial-origins.edit')->name('edit');
                Route::put('{commercialOrigin}', [CommercialOriginController::class, 'update'])->whereNumber('commercialOrigin')->middleware('permission:commercial-origins.edit')->name('update');
                Route::delete('{commercialOrigin}', [CommercialOriginController::class, 'destroy'])->whereNumber('commercialOrigin')->middleware('permission:commercial-origins.delete')->name('destroy');
            });
            Route::prefix('products')
                ->name('products.')
                ->group(function (): void {
                    Route::get('/', [ProductController::class, 'index'])
                        ->middleware('permission:products.view')
                        ->name('index');
                    Route::get('create', [ProductController::class, 'create'])
                        ->middleware('permission:products.create')
                        ->name('create');
                    Route::post('/', [ProductController::class, 'store'])
                        ->middleware('permission:products.create')
                        ->name('store');
                    Route::get('{product}/edit', [ProductController::class, 'edit'])
                        ->whereNumber('product')
                        ->middleware('permission:products.edit')
                        ->name('edit');
                    Route::put('{product}', [ProductController::class, 'update'])
                        ->whereNumber('product')
                        ->middleware('permission:products.edit')
                        ->name('update');
                    Route::delete('{product}', [ProductController::class, 'destroy'])
                        ->whereNumber('product')
                        ->middleware('permission:products.delete')
                        ->name('destroy');
                });
            Route::prefix('authorization')
                ->name('authorization.')
                ->group(function (): void {
                    Route::get('/', [SinAuthorizationController::class, 'index'])
                        ->middleware('permission:sin-authorizations.view')
                        ->name('index');
                    Route::post('/', [SinAuthorizationController::class, 'store'])
                        ->middleware('permission:sin-authorizations.manage')
                        ->name('store');
                    Route::put('/', [SinAuthorizationController::class, 'update'])
                        ->middleware('permission:sin-authorizations.manage')
                        ->name('update');
                });
            Route::prefix('categories')
                ->name('categories.')
                ->group(function (): void {
                    Route::get('/', [ProductCategoryController::class, 'index'])
                        ->middleware('permission:product-categories.view')
                        ->name('index');
                    Route::get('create', [ProductCategoryController::class, 'create'])
                        ->middleware('permission:product-categories.create')
                        ->name('create');
                    Route::post('/', [ProductCategoryController::class, 'store'])
                        ->middleware('permission:product-categories.create')
                        ->name('store');
                    Route::get('{productCategory}/edit', [ProductCategoryController::class, 'edit'])
                        ->whereNumber('productCategory')
                        ->middleware('permission:product-categories.edit')
                        ->name('edit');
                    Route::put('{productCategory}', [ProductCategoryController::class, 'update'])
                        ->whereNumber('productCategory')
                        ->middleware('permission:product-categories.edit')
                        ->name('update');
                    Route::delete('{productCategory}', [ProductCategoryController::class, 'destroy'])
                        ->whereNumber('productCategory')
                        ->middleware('permission:product-categories.delete')
                        ->name('destroy');
                });
            Route::prefix('customers')
                ->name('customers.')
                ->group(function (): void {
                    Route::get('/', [CustomerController::class, 'index'])
                        ->middleware('permission:customers.view')
                        ->name('index');
                    Route::get('create', [CustomerController::class, 'create'])
                        ->middleware('permission:customers.create')
                        ->name('create');
                    Route::post('/', [CustomerController::class, 'store'])
                        ->middleware('permission:customers.create')
                        ->name('store');
                    Route::get('{customer}/edit', [CustomerController::class, 'edit'])
                        ->whereNumber('customer')
                        ->middleware('permission:customers.edit')
                        ->name('edit');
                    Route::put('{customer}', [CustomerController::class, 'update'])
                        ->whereNumber('customer')
                        ->middleware('permission:customers.edit')
                        ->name('update');
                    Route::delete('{customer}', [CustomerController::class, 'destroy'])
                        ->whereNumber('customer')
                        ->middleware('permission:customers.delete')
                        ->name('destroy');
                });
        });
    Route::prefix('inscripciones')
        ->name('rectorate.')
        ->middleware('company_user')
        ->group(function (): void {
            Route::get('cuentas-por-cobrar', [AccountStatementController::class, 'index'])
                ->middleware('permission:accounts.collect')
                ->name('collectible-accounts.index');
            Route::get('/', [NewApplicationController::class, 'index'])
                ->middleware('permission:rectorate.create')
                ->name('index');
            Route::get('nuevo/buscar-titular', [NewApplicationController::class, 'lookup'])
                ->middleware('permission:rectorate.create')
                ->name('new.lookup');
            Route::get('nuevo', [NewApplicationController::class, 'create'])
                ->middleware('permission:rectorate.create')
                ->name('new');
            Route::post('nuevo', [NewApplicationController::class, 'store'])
                ->middleware('permission:rectorate.create')
                ->name('new.store');
            Route::get('{application}/titular', [NewApplicationController::class, 'editHolder'])
                ->whereNumber('application')->middleware('permission:rectorate.create')
                ->name('applications.holder.edit');
            Route::put('{application}/titular', [NewApplicationController::class, 'updateHolder'])
                ->whereNumber('application')->middleware('permission:rectorate.create')
                ->name('applications.holder.update');
            Route::get('{application}/plan', [NewApplicationController::class, 'editPlan'])
                ->whereNumber('application')->middleware('permission:rectorate.create')
                ->name('applications.plan.edit');
            Route::put('{application}/plan', [NewApplicationController::class, 'updatePlan'])
                ->whereNumber('application')->middleware('permission:rectorate.create')
                ->name('applications.plan.update');
            Route::get('{application}/estudiante', [NewApplicationController::class, 'editStudent'])
                ->whereNumber('application')->middleware('permission:rectorate.create')
                ->name('applications.student.edit');
            Route::put('{application}/estudiante', [NewApplicationController::class, 'updateStudent'])
                ->whereNumber('application')->middleware('permission:rectorate.create')
                ->name('applications.student.update');
            Route::get('{application}/confirmacion', [NewApplicationController::class, 'confirmation'])
                ->whereNumber('application')->middleware('permission:rectorate.create')
                ->name('applications.confirmation.show');
            Route::post('{application}/confirmacion', [NewApplicationController::class, 'confirm'])
                ->whereNumber('application')->middleware('permission:rectorate.create')
                ->name('applications.confirmation.store');
            Route::get('contratos/{contract}/estado-cuenta', [AccountStatementController::class, 'show'])
                ->whereNumber('contract')->middleware('permission:accounts.collect')
                ->name('contracts.account.show');
            Route::get('contratos/{contract}/imprimir', [NewApplicationController::class, 'printContract'])
                ->whereNumber('contract')->middleware('permission:rectorate.create')
                ->name('contracts.print');
            Route::post('contratos/{contract}/pagos', [AccountStatementController::class, 'store'])
                ->whereNumber('contract')->middleware('permission:accounts.collect')
                ->name('contracts.payments.store');
            Route::delete('{application}', [NewApplicationController::class, 'destroy'])
                ->whereNumber('application')->middleware('permission:rectorate.delete')
                ->name('applications.destroy');
        });
    Route::prefix('datatables')->name('datatables.')->group(function (): void {
        Route::get('audits', [AdminDataTableController::class, 'audits'])->name('audits');
        Route::get('invoices', [AdminDataTableController::class, 'invoices'])
            ->middleware(['company_user', 'permission:invoices.view'])
            ->name('invoices');
        Route::get('siat/catalogs/{catalog}/items', [AdminDataTableController::class, 'siatCatalogItems'])
            ->middleware('permission:siat-catalogs.view')
            ->name('siat-catalog-items');
    });
    Route::prefix('users')->name('users.')->group(function (): void {
        Route::get('/', [UserController::class, 'index'])->middleware('permission:users.view')->name('index');
        Route::get('create', [UserController::class, 'create'])->middleware('permission:users.create')->name('create');
        Route::post('/', [UserController::class, 'store'])->middleware('permission:users.create')->name('store');
        Route::get('{user}', [UserController::class, 'show'])->middleware('permission:users.view')->withTrashed()->name('show');
        Route::get('{user}/edit', [UserController::class, 'edit'])->middleware('permission:users.edit')->name('edit');
        Route::put('{user}', [UserController::class, 'update'])->middleware('permission:users.edit')->name('update');
        Route::patch('{user}/toggle-status', [UserController::class, 'toggleStatus'])->middleware('permission:users.edit')->name('toggle-status');
        Route::patch('{user}/reset-password', [UserController::class, 'resetPassword'])->middleware('permission:users.change-password')->name('reset-password');
        Route::get('{user}/roles', [UserController::class, 'rolesForm'])->middleware('permission:users.assign-roles')->name('roles.form');
        Route::patch('{user}/assign-roles', [UserController::class, 'assignRoles'])->middleware('permission:users.assign-roles')->name('assign-roles');
        Route::delete('{user}', [UserController::class, 'destroy'])->middleware('permission:users.delete')->name('destroy');
        Route::patch('{user}/restore', [UserController::class, 'restore'])->middleware('permission:users.restore')->name('restore');
    });
    Route::resource('areas', AreaController::class)->except('show')->middleware([
        'index' => 'permission:areas.view', 'create' => 'permission:areas.manage', 'store' => 'permission:areas.manage',
        'edit' => 'permission:areas.manage', 'update' => 'permission:areas.manage', 'destroy' => 'permission:areas.manage',
    ]);
    Route::prefix('campuses')->name('campuses.')->middleware('company_user')->group(function (): void {
        Route::get('/', [CampusController::class, 'index'])->middleware('permission:campuses.view')->name('index');
        Route::get('create', [CampusController::class, 'create'])->middleware('permission:campuses.manage')->name('create');
        Route::post('/', [CampusController::class, 'store'])->middleware('permission:campuses.manage')->name('store');
        Route::get('{campus}/edit', [CampusController::class, 'edit'])->whereNumber('campus')->middleware('permission:campuses.manage')->name('edit');
        Route::put('{campus}', [CampusController::class, 'update'])->whereNumber('campus')->middleware('permission:campuses.manage')->name('update');
        Route::delete('{campus}', [CampusController::class, 'destroy'])->whereNumber('campus')->middleware('permission:campuses.manage')->name('destroy');
    });
    Route::resource('positions', PositionController::class)->except('show')->middleware([
        'index' => 'permission:positions.view', 'create' => 'permission:positions.manage', 'store' => 'permission:positions.manage',
        'edit' => 'permission:positions.manage', 'update' => 'permission:positions.manage', 'destroy' => 'permission:positions.manage',
    ]);
    Route::get('personnel/lookup/identity-document', [PersonnelController::class, 'lookup'])
        ->middleware('permission:personnel.view|personnel.manage')->name('personnel.lookup');
    Route::resource('personnel', PersonnelController::class)->middleware([
        'index' => 'permission:personnel.view', 'show' => 'permission:personnel.view',
        'create' => 'permission:personnel.manage', 'store' => 'permission:personnel.manage', 'edit' => 'permission:personnel.manage',
        'update' => 'permission:personnel.manage', 'destroy' => 'permission:personnel.manage',
    ]);
    Route::prefix('roles')->name('roles.')->group(function (): void {
        Route::get('/', [RoleController::class, 'index'])->middleware('permission:roles.view')->name('index');
        Route::get('create', [RoleController::class, 'create'])->middleware('permission:roles.create')->name('create');
        Route::post('/', [RoleController::class, 'store'])->middleware('permission:roles.create')->name('store');
        Route::get('{role}', [RoleController::class, 'show'])->middleware('permission:roles.view')->name('show');
        Route::get('{role}/edit', [RoleController::class, 'edit'])->middleware('permission:roles.edit')->name('edit');
        Route::put('{role}', [RoleController::class, 'update'])->middleware('permission:roles.edit')->name('update');
        Route::get('{role}/permissions', [RoleController::class, 'permissionsForm'])->middleware('permission:roles.assign-permissions')->name('permissions.form');
        Route::patch('{role}/permissions', [RoleController::class, 'assignPermissions'])->middleware('permission:roles.assign-permissions')->name('permissions');
        Route::delete('{role}', [RoleController::class, 'destroy'])->middleware('permission:roles.delete')->name('destroy');
    });
    Route::resource('permissions', PermissionController::class)->middleware([
        'index' => 'permission:permissions.view',
        'show' => 'permission:permissions.view',
        'create' => 'permission:permissions.create',
        'store' => 'permission:permissions.create',
        'edit' => 'permission:permissions.edit',
        'update' => 'permission:permissions.edit',
        'destroy' => 'permission:permissions.delete',
    ]);
});
