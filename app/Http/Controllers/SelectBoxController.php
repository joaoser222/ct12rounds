<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessAction;
use App\AccessControl\AccessModule;
use App\Models\Client;
use App\Models\CostCenter;
use App\Models\Coupon;
use App\Models\FinancialAccount;
use App\Models\FinancialCategory;
use App\Models\Modality;
use App\Models\Plan;
use App\Models\PlanCategory;
use App\Models\Product;
use App\Models\ProductUnity;
use App\Models\Supplier;
use App\Models\Trainer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SelectBoxController extends Controller
{
    public function __invoke(Request $request, string $objectName): JsonResponse
    {
        $method = 'select'.str_replace('-', '', ucwords($objectName, '-'));

        if (! method_exists($this, $method)) {
            abort(404, "Select for '{$objectName}' not found.");
        }

        $this->authorizeAccess($this->accessModuleFor($objectName));

        return $this->{$method}($request);
    }

    private function authorizeAccess(AccessModule $module): void
    {
        Gate::authorize($module->value.'.'.AccessAction::VIEW->value);
    }

    private function accessModuleFor(string $objectName): AccessModule
    {
        return match ($objectName) {
            'client' => AccessModule::CLIENT,
            'financial-category' => AccessModule::FINANCIAL_CATEGORY,
            'cost-center' => AccessModule::COST_CENTER,
            'coupon' => AccessModule::COUPON,
            'modality' => AccessModule::MODALITY,
            'plan' => AccessModule::PLAN,
            'plan-category' => AccessModule::PLAN_CATEGORY,
            'product', 'product-unity' => AccessModule::PRODUCT,
            'financial-account' => AccessModule::FINANCIAL_ACCOUNT,
            'supplier' => AccessModule::SUPPLIER,
            'trainer' => AccessModule::TRAINER,
        };
    }

    private function selectClient(Request $request): JsonResponse
    {
        return $this->search(Client::class, $request, fn (Builder $q) => $q->where('status', 'active'));
    }

    private function selectFinancialCategory(Request $request): JsonResponse
    {
        return $this->search(FinancialCategory::class, $request, fn (Builder $q) => $q->where('visibility', 'visible'));
    }

    private function selectCostCenter(Request $request): JsonResponse
    {
        return $this->search(CostCenter::class, $request, fn (Builder $q) => $q->where('visibility', 'visible'));
    }

    private function selectCoupon(Request $request): JsonResponse
    {
        return $this->search(
            Coupon::class,
            $request,
            fn (Builder $q) => $q->where('visibility', 'visible'),
            'code',
            'id',
            ['percent', 'discount_limit', 'expiration_date'],
        );
    }

    private function selectModality(Request $request): JsonResponse
    {
        return $this->search(Modality::class, $request);
    }

    private function selectPlan(Request $request): JsonResponse
    {
        return $this->search(Plan::class, $request);
    }

    private function selectPlanCategory(Request $request): JsonResponse
    {
        return $this->search(PlanCategory::class, $request);
    }

    private function selectProduct(Request $request): JsonResponse
    {
        return $this->search(Product::class, $request, null, 'name', 'id', ['purchase_price', 'sale_price']);
    }

    private function selectFinancialAccount(Request $request): JsonResponse
    {
        return $this->search(FinancialAccount::class, $request, null, 'name', 'id');
    }

    private function selectProductUnity(Request $request): JsonResponse
    {
        return $this->search(ProductUnity::class, $request, null, 'name', 'code');
    }

    private function selectSupplier(Request $request): JsonResponse
    {
        return $this->search(Supplier::class, $request);
    }

    private function selectTrainer(Request $request): JsonResponse
    {
        return $this->search(Trainer::class, $request);
    }

    /**
     * Generic search for selectboxes.
     *
     * @param  class-string<Model>  $modelClass
     */
    private function search(
        string $modelClass,
        Request $request,
        ?\Closure $extraFilters = null,
        string $optionName = 'name',
        string $optionValue = 'id',
        array $extraAttributes = []
    ): JsonResponse {
        $search = $request->input('search', '');
        $selected = $request->input('selected');
        $limit = min((int) $request->input('limit', 15), 50);

        $query = $modelClass::query()
            ->select([$optionValue, $optionName, ...$extraAttributes])
            ->orderBy($optionName);

        if ($extraFilters !== null) {
            $query = $extraFilters($query);
        }

        if ($search !== '') {
            $query->where($optionName, 'like', '%'.$search.'%');
        }

        $options = $query->limit($limit)
            ->get()
            ->map(fn (mixed $model): array => [
                'value' => (string) $model->getAttribute($optionValue),
                'label' => (string) $model->getAttribute($optionName),
                ...collect($extraAttributes)
                    ->mapWithKeys(fn (string $attribute): array => [
                        $attribute => $model->getAttribute($attribute),
                    ])
                    ->all(),
            ])
            ->values();

        if ($selected !== null && $selected !== '') {
            $selectedOption = $modelClass::query()
                ->select([$optionValue, $optionName, ...$extraAttributes])
                ->where($optionValue, $selected)
                ->first();

            if ($selectedOption !== null) {
                $options->prepend([
                    'value' => (string) $selectedOption->getAttribute($optionValue),
                    'label' => (string) $selectedOption->getAttribute($optionName),
                    ...collect($extraAttributes)
                        ->mapWithKeys(fn (string $attribute): array => [
                            $attribute => $selectedOption->getAttribute($attribute),
                        ])
                        ->all(),
                ]);

                $options = $options->unique('value')->values();
            }
        }

        return response()->json($options->all());
    }
}
