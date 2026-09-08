<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessAction;
use App\AccessControl\AccessModule;
use App\Actions\Plans\CreatePlanAction;
use App\Actions\Plans\UpdatePlanAction;
use App\DTOs\Plans\CreatePlanDTO;
use App\DTOs\Plans\UpdatePlanDTO;
use App\Http\Requests\PlanRequest;
use App\Models\Modality;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlanController extends CrudModuleController
{
    public function __construct(
        private readonly CreatePlanAction $createPlan,
        private readonly UpdatePlanAction $updatePlan,
    ) {}

    /**
     * @var array<int, string>
     */
    protected array $fields = ['id', 'name', 'description', 'created_at'];

    /**
     * @var array<int, string>
     */
    protected array $searchableFields = ['name'];

    /**
     * @var array<int, string>
     */
    protected array $sortableFields = ['id', 'name', 'created_at'];

    protected function accessModule(): AccessModule
    {
        return AccessModule::PLAN;
    }

    protected function modelClass(): string
    {
        return Plan::class;
    }

    protected function storeRequestClass(): ?string
    {
        return PlanRequest::class;
    }

    protected function updateRequestClass(): ?string
    {
        return PlanRequest::class;
    }

    /**
     * @return array<string, mixed>
     */
    protected function moduleDetailsProps(?Model $model = null): array
    {
        return [
            'options' => [
                'modalities' => Modality::query()
                    ->select(['id', 'name'])
                    ->orderBy('name')
                    ->get()
                    ->map(fn (Modality $modality): array => [
                        'value' => $modality->id,
                        'label' => $modality->name,
                    ])
                    ->all(),
            ],
            'publicRegistrationUrl' => $model ? $this->publicRegistrationUrl($model) : null,
        ];
    }

    private function publicRegistrationUrl(Model $model): ?string
    {
        if (empty($model->getAttribute('public_slug')) || $model->getAttribute('visibility') === 'hidden') {
            return null;
        }

        return route('public.register', ['plan' => $model->getAttribute('public_slug')]);
    }

    public function show(Request $request): Response|JsonResponse
    {
        $this->authorizeAccess(AccessAction::VIEW);

        /** @var Plan $plan */
        $plan = $this->modelFromRoute($request)->load('modalities');
        $plan->setAttribute('plan_modalities', $plan->modalities->pluck('modality_id')->all());

        if ($request->expectsJson()) {
            return response()->json($plan);
        }

        $this->shareModuleRoutes();

        return Inertia::render($this->detailsComponent(), [
            $this->itemPropName() => $plan,
            'id' => $plan->getKey(),
            'routes' => $this->getModuleRoutes(),
            ...$this->moduleDetailsProps($plan),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeAccess(AccessAction::CREATE);

        $result = $this->createPlan->execute(
            CreatePlanDTO::fromArray(
                $this->validatedRequestData($request, $this->storeRequestClass())
            )
        );

        /** @var Plan $plan */
        $plan = $result->data;

        if ($request->expectsJson()) {
            return response()->json($plan, 201);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __($this->accessModule()->label().' criado com sucesso.'),
        ]);

        return redirect()->route($this->routePrefix().'.index');
    }

    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeAccess(AccessAction::UPDATE);

        /** @var Plan $model */
        $model = $this->modelFromRoute($request);

        $result = $this->updatePlan->execute(
            UpdatePlanDTO::fromArray([
                ...$this->validatedRequestData($request, $this->updateRequestClass()),
                'id' => $model->getKey(),
            ])
        );

        /** @var Plan $plan */
        $plan = $result->data;

        if ($request->expectsJson()) {
            return response()->json($plan);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __($this->accessModule()->label().' atualizado com sucesso.'),
        ]);

        return redirect()->route($this->routePrefix().'.index');
    }
}
