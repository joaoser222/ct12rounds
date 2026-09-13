<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\UserPermissionsController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\CostCenterController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DirectLessonController;
use App\Http\Controllers\FinancialAccountController;
use App\Http\Controllers\FinancialCategoryController;
use App\Http\Controllers\GatewayAccountController;
use App\Http\Controllers\GatewayCreditCardController;
use App\Http\Controllers\GatewayCustomerController;
use App\Http\Controllers\GatewayInvoiceController;
use App\Http\Controllers\GatewayPaymentController;
use App\Http\Controllers\GatewayPostbackController;
use App\Http\Controllers\GatewayTransferController;
use App\Http\Controllers\GatewayTransferRecipientController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\HiringLeadController;
use App\Http\Controllers\LandingAdmin\EditorController as LandingAdminEditorController;
use App\Http\Controllers\LandingAdmin\Auth\LoginController as LandingAdminLoginController;
use App\Http\Controllers\LandingAssetController;
use App\Http\Controllers\LandingStorageController;
use App\Http\Controllers\ModalityController;
use App\Http\Controllers\MovementController;
use App\Http\Controllers\PayableController;
use App\Http\Controllers\PlanCategoryController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PublicHiringLeadController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReceivableController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SelectBoxController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TrainerController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('storage/landing/{path}', [LandingStorageController::class, 'show'])
    ->name('landing.storage')
    ->where('path', '[A-Za-z0-9._-]+');

Route::get('landing-assets/{path}', [LandingAssetController::class, 'show'])
    ->name('landing.assets')
    ->where('path', 'img/[A-Za-z0-9._()% +-]+');

Route::prefix('landing-admin')->name('landing-admin.')->group(function () {
    Route::middleware('guest:landing_admin')->group(function () {
        Route::get('login', [LandingAdminLoginController::class, 'create'])->name('login');
        Route::post('login', [LandingAdminLoginController::class, 'store'])->name('login.store');
    });

    Route::middleware('auth:landing_admin')->group(function () {
        Route::get('/', [LandingAdminEditorController::class, 'index'])->name('editor');
        Route::put('api/content', [LandingAdminEditorController::class, 'saveDraft'])->name('api.content');
        Route::post('api/publish', [LandingAdminEditorController::class, 'publish'])->name('api.publish');
        Route::post('api/images', [LandingAdminEditorController::class, 'uploadImage'])->name('api.images');
        Route::post('logout', [LandingAdminLoginController::class, 'destroy'])->name('logout');
    });
});

Route::post('gateway-postbacks/{gateway_account}/receive', [GatewayPostbackController::class, 'receive'])
    ->name('gateway-postbacks.receive');

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->name('login.store');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.update');
});

Route::middleware('throttle:30,1')->group(function () {
    Route::post('/', [HomeController::class, 'store'])->name('public.landing.store');
    Route::get('register', [PublicHiringLeadController::class, 'create'])->name('public.register');
    Route::post('register', [PublicHiringLeadController::class, 'store'])->name('public.register.store');
    Route::post('register/retry-payment', [PublicHiringLeadController::class, 'retryPayment'])->name('public.register.retry');
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('auth/permissions', UserPermissionsController::class)->name('auth.permissions');

    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('chat', fn () => inertia('Chat'))->name('chat')->can('chat.view');
    Route::get('chat/prompts', [ChatController::class, 'prompts'])->name('chat.prompts')->can('chat.view');
    Route::post('chat/message', [ChatController::class, 'message'])->name('chat.message')->can('chat.view');
    Route::get('chat/conversations', [ChatController::class, 'conversations'])->name('chat.conversations')->can('chat.view');
    Route::get('chat/conversations/{conversation}', [ChatController::class, 'show'])->name('chat.conversations.show')->can('chat.view');
    Route::get('select-box/{objectName}', SelectBoxController::class)->name('select-box');
    Route::patch('contracts/{contract}/apply', [ContractController::class, 'apply'])->name('contracts.apply');
    Route::patch('contracts/{contract}/cancel', [ContractController::class, 'cancel'])->name('contracts.cancel');

    // Pessoas
    Route::module(ClientController::class);
    Route::module(TrainerController::class);
    Route::module(SupplierController::class);
    Route::prefix('hiring-leads')->name('hiring-leads.')->group(function () {
        Route::get('/', [HiringLeadController::class, 'index'])->name('index');
        Route::get('/{hiring_lead}', [HiringLeadController::class, 'show'])->name('show');
        Route::delete('/', [HiringLeadController::class, 'destroy'])->name('destroy');
        Route::patch('/change-visibility', [HiringLeadController::class, 'changeVisibility'])->name('change-visibility');
    });
    Route::post('hiring-leads/{hiring_lead}/convert', [HiringLeadController::class, 'convert'])->name('hiring-leads.convert');

    // Catálogo
    Route::module(ProductController::class);
    Route::module(ModalityController::class);
    Route::module(PlanController::class);
    Route::module(PlanCategoryController::class);

    // Faturamento
    Route::module(ContractController::class);
    Route::module(CouponController::class);
    Route::module(SaleController::class);
    Route::module(PurchaseController::class);
    Route::module(DirectLessonController::class);

    // Financeiro
    Route::module(CostCenterController::class);
    Route::module(FinancialCategoryController::class);
    Route::module(PayableController::class);
    Route::module(ReceivableController::class);
    Route::patch('receivables/{receivable}/mark-paid', [ReceivableController::class, 'markPaid'])->name('receivables.mark-paid');
    Route::post('receivables/{receivable}/request-gateway-invoice', [ReceivableController::class, 'requestGatewayInvoice'])->name('receivables.request-gateway-invoice');
    Route::moduleReadOnly(MovementController::class);

    // Gateway de Pagamentos
    Route::moduleReadOnly(GatewayPaymentController::class);
    Route::get('gateway-transfers/create', [GatewayTransferController::class, 'create'])->name('gateway-transfers.create');
    Route::moduleReadOnly(GatewayTransferController::class);
    Route::post('gateway-transfers', [GatewayTransferController::class, 'store'])->name('gateway-transfers.store');
    Route::module(GatewayTransferRecipientController::class);
    Route::moduleReadOnly(GatewayPostbackController::class);
    Route::moduleReadOnly(GatewayCustomerController::class);
    Route::moduleReadOnly(GatewayCreditCardController::class);
    Route::moduleReadOnly(GatewayInvoiceController::class);

    // Relatórios
    Route::moduleReadOnly(ReportController::class);

    // Avançado
    Route::module(FinancialAccountController::class);
    Route::module(GatewayAccountController::class);
    Route::get('gateway-accounts/{gateway_account}/invoicing/municipal-options', [GatewayAccountController::class, 'municipalOptions'])->name('gateway-accounts.invoicing.municipal-options');
    Route::get('gateway-accounts/{gateway_account}/invoicing/municipal-services', [GatewayAccountController::class, 'municipalServices'])->name('gateway-accounts.invoicing.municipal-services');
    Route::put('gateway-accounts/{gateway_account}/invoicing/municipal-configuration', [GatewayAccountController::class, 'configureFiscalData'])->name('gateway-accounts.invoicing.municipal-configuration');
    Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
    Route::get('roles/{role}', [RoleController::class, 'show'])->name('roles.show');
    Route::put('roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    Route::module(UserController::class);
    Route::get('settings', [SettingController::class, 'index'])->name('settings.show');
    Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
});
