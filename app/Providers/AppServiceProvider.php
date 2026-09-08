<?php

namespace App\Providers;

use App\AccessControl\AccessModule;
use App\Models\Invoice;
use App\Models\User;
use App\PaymentGateways\Adapters\AsaasPaymentGatewayAdapter;
use App\PaymentGateways\Contracts\PaymentGatewayAdapter;
use App\PaymentGateways\Contracts\PaymentGatewayInvoicingAdapter;
use App\PaymentGateways\PaymentGatewayManager;
use App\Repositories\Contracts\ClientRepositoryInterface;
use App\Repositories\Contracts\ContractRepositoryInterface;
use App\Repositories\Contracts\CostCenterRepositoryInterface;
use App\Repositories\Contracts\CouponRepositoryInterface;
use App\Repositories\Contracts\DirectLessonRepositoryInterface;
use App\Repositories\Contracts\FinancialAccountRepositoryInterface;
use App\Repositories\Contracts\FinancialCategoryRepositoryInterface;
use App\Repositories\Contracts\GatewayAccountRepositoryInterface;
use App\Repositories\Contracts\GatewayInvoiceRepositoryInterface;
use App\Repositories\Contracts\GatewayPaymentRepositoryInterface;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use App\Repositories\Contracts\ModalityRepositoryInterface;
use App\Repositories\Contracts\MovementRepositoryInterface;
use App\Repositories\Contracts\PayableRepositoryInterface;
use App\Repositories\Contracts\PlanCategoryRepositoryInterface;
use App\Repositories\Contracts\PlanRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\PurchaseRepositoryInterface;
use App\Repositories\Contracts\ReceivableRepositoryInterface;
use App\Repositories\Contracts\SaleRepositoryInterface;
use App\Repositories\Contracts\SupplierRepositoryInterface;
use App\Repositories\Contracts\TrainerRepositoryInterface;
use App\Repositories\Eloquent\EloquentClientRepository;
use App\Repositories\Eloquent\EloquentContractRepository;
use App\Repositories\Eloquent\EloquentCostCenterRepository;
use App\Repositories\Eloquent\EloquentCouponRepository;
use App\Repositories\Eloquent\EloquentDirectLessonRepository;
use App\Repositories\Eloquent\EloquentFinancialAccountRepository;
use App\Repositories\Eloquent\EloquentFinancialCategoryRepository;
use App\Repositories\Eloquent\EloquentGatewayAccountRepository;
use App\Repositories\Eloquent\EloquentGatewayInvoiceRepository;
use App\Repositories\Eloquent\EloquentGatewayPaymentRepository;
use App\Repositories\Eloquent\EloquentInvoiceRepository;
use App\Repositories\Eloquent\EloquentModalityRepository;
use App\Repositories\Eloquent\EloquentMovementRepository;
use App\Repositories\Eloquent\EloquentPayableRepository;
use App\Repositories\Eloquent\EloquentPlanCategoryRepository;
use App\Repositories\Eloquent\EloquentPlanRepository;
use App\Repositories\Eloquent\EloquentProductRepository;
use App\Repositories\Eloquent\EloquentPurchaseRepository;
use App\Repositories\Eloquent\EloquentReceivableRepository;
use App\Repositories\Eloquent\EloquentSaleRepository;
use App\Repositories\Eloquent\EloquentSupplierRepository;
use App\Repositories\Eloquent\EloquentTrainerRepository;
use App\Services\Billing\BillingSourceResolver;
use App\Services\Billing\DiscountCalculator;
use App\Services\Billing\InstallmentSplitter;
use App\Services\Billing\InvoiceGenerator;
use App\Services\Gateway\FiscalInvoiceEmitter;
use App\Services\Gateway\FiscalSyncOrchestrator;
use App\Services\Gateway\GatewayAdapterResolver;
use App\Services\Gateway\GatewayBillingOrchestrator;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PaymentGatewayAdapter::class, AsaasPaymentGatewayAdapter::class);
        $this->app->bind(
            PaymentGatewayInvoicingAdapter::class,
            fn ($app): PaymentGatewayInvoicingAdapter => $app->make(AsaasPaymentGatewayAdapter::class),
        );
        $this->app->singleton(PaymentGatewayManager::class);

        $this->registerRepositoryBindings();
        $this->registerServiceBindings();

        // Generate routes in a custom way for modules
        Route::macro('module', function (string $prefixOrController, ?string $controller = null) {
            $controller ??= $prefixOrController;
            $prefix = $controller === $prefixOrController
                ? Str::of(class_basename($controller))->beforeLast('Controller')->plural()->kebab()->toString()
                : $prefixOrController;
            $param = Str::of($prefix)->replace('-', '_')->singular()->toString();

            Route::prefix($prefix)->name("{$prefix}.")->group(function () use ($controller, $param) {
                Route::get('/', [$controller, 'index'])->name('index');
                Route::get('/create', [$controller, 'create'])->name('create');
                Route::get("/{{$param}}", [$controller, 'show'])->name('show');
                Route::post('/', [$controller, 'store'])->name('store');
                Route::put("/{{$param}}", [$controller, 'update'])->name('update');
                Route::delete('/', [$controller, 'destroy'])->name('destroy');
                Route::patch('/change-visibility', [$controller, 'changeVisibility'])->name('change-visibility');
            });
        });

        Route::macro('moduleReadOnly', function (string $prefixOrController, ?string $controller = null) {
            $controller ??= $prefixOrController;
            $prefix = $controller === $prefixOrController
                ? Str::of(class_basename($controller))->beforeLast('Controller')->plural()->kebab()->toString()
                : $prefixOrController;
            $param = Str::of($prefix)->replace('-', '_')->singular()->toString();

            Route::prefix($prefix)->name("{$prefix}.")->group(function () use ($controller, $param) {
                Route::get('/', [$controller, 'index'])->name('index');
                Route::get("/{{$param}}", [$controller, 'show'])->name('show');
            });
        });
    }

    /**
     * Bind repository interfaces to their Eloquent implementations.
     */
    protected function registerRepositoryBindings(): void
    {
        $this->app->bind(ContractRepositoryInterface::class, EloquentContractRepository::class);
        $this->app->bind(InvoiceRepositoryInterface::class, EloquentInvoiceRepository::class);
        $this->app->bind(GatewayAccountRepositoryInterface::class, EloquentGatewayAccountRepository::class);
        $this->app->bind(ClientRepositoryInterface::class, EloquentClientRepository::class);
        $this->app->bind(SaleRepositoryInterface::class, EloquentSaleRepository::class);
        $this->app->bind(PurchaseRepositoryInterface::class, EloquentPurchaseRepository::class);
        $this->app->bind(DirectLessonRepositoryInterface::class, EloquentDirectLessonRepository::class);
        $this->app->bind(ReceivableRepositoryInterface::class, EloquentReceivableRepository::class);
        $this->app->bind(PayableRepositoryInterface::class, EloquentPayableRepository::class);
        $this->app->bind(MovementRepositoryInterface::class, EloquentMovementRepository::class);
        $this->app->bind(ProductRepositoryInterface::class, EloquentProductRepository::class);
        $this->app->bind(PlanRepositoryInterface::class, EloquentPlanRepository::class);
        $this->app->bind(PlanCategoryRepositoryInterface::class, EloquentPlanCategoryRepository::class);
        $this->app->bind(ModalityRepositoryInterface::class, EloquentModalityRepository::class);
        $this->app->bind(CouponRepositoryInterface::class, EloquentCouponRepository::class);
        $this->app->bind(SupplierRepositoryInterface::class, EloquentSupplierRepository::class);
        $this->app->bind(TrainerRepositoryInterface::class, EloquentTrainerRepository::class);
        $this->app->bind(GatewayPaymentRepositoryInterface::class, EloquentGatewayPaymentRepository::class);
        $this->app->bind(GatewayInvoiceRepositoryInterface::class, EloquentGatewayInvoiceRepository::class);
        $this->app->bind(FinancialCategoryRepositoryInterface::class, EloquentFinancialCategoryRepository::class);
        $this->app->bind(CostCenterRepositoryInterface::class, EloquentCostCenterRepository::class);
        $this->app->bind(FinancialAccountRepositoryInterface::class, EloquentFinancialAccountRepository::class);
    }

    /**
     * Bind service interfaces to their implementations.
     */
    protected function registerServiceBindings(): void
    {
        // Billing services (pure logic - stateless)
        $this->app->singleton(DiscountCalculator::class);
        $this->app->singleton(InstallmentSplitter::class);
        $this->app->singleton(BillingSourceResolver::class);

        // InvoiceGenerator (uses repositories)
        $this->app->singleton(InvoiceGenerator::class);

        // Gateway services
        $this->app->singleton(GatewayAdapterResolver::class);
        $this->app->singleton(GatewayBillingOrchestrator::class);
        $this->app->singleton(FiscalInvoiceEmitter::class);
        $this->app->singleton(FiscalSyncOrchestrator::class);
    }

    public function boot(): void
    {
        $this->registerMorphMaps();

        Vite::useHotFile(storage_path('vite/hot'));

        $this->registerAccessControlGates();
        $this->configureDefaults();
    }

    /**
     * Register the global polymorphic maps so morph aliases resolve
     * consistently before any model is booted.
     */
    protected function registerMorphMaps(): void
    {
        $invoice = new Invoice;

        foreach ($invoice->getAllMorphConfigs() as $config) {
            Relation::morphMap($config['map']);
        }
    }

    protected function registerAccessControlGates(): void
    {
        foreach (AccessModule::cases() as $module) {
            foreach ($module->actions() as $action) {
                $permissionName = $module->value.'.'.$action->value;

                Gate::define($permissionName, static fn (User $user): bool => $user->permissions()
                    ->where('name', $permissionName)
                    ->exists());
            }
        }
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
