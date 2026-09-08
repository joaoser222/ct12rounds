<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessAction;
use App\AccessControl\AccessModule;
use App\Actions\HiringLeads\ConvertHiringLeadAction;
use App\Enums\GenderType;
use App\Enums\HiringLeadSource;
use App\Enums\HiringLeadStatus;
use App\Models\Coupon;
use App\Models\HiringLead;
use App\Models\Plan;
use App\Models\State;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HiringLeadController extends CrudModuleController
{
    public function __construct(
        private readonly ConvertHiringLeadAction $convertHiringLead,
    ) {}

    /**
     * @var array<int, string>
     */
    protected array $fields = ['id', 'name', 'document', 'phone', 'email', 'source', 'status', 'created_at'];

    /**
     * @var array<int, string>
     */
    protected array $searchableFields = ['name', 'email', 'document', 'phone'];

    /**
     * @var array<int, string>
     */
    protected array $sortableFields = ['id', 'source', 'status', 'created_at'];

    protected function accessModule(): AccessModule
    {
        return AccessModule::HIRING_LEAD;
    }

    protected function modelClass(): string
    {
        return HiringLead::class;
    }

    /**
     * Pre-registration does not allow creation or editing: only viewing,
     * visibility changes, deletion, and conversion to a client.
     *
     * @return array<string, string>
     */
    protected function getModuleRoutes(): array
    {
        $prefix = $this->routePrefix();
        $parameterName = $this->routeParameterName();
        $showRoute = route("{$prefix}.show", [$parameterName => '__id__']);

        return [
            'index' => route("{$prefix}.index"),
            'show' => str_replace('__id__', ':id', $showRoute),
            'changeVisibility' => route("{$prefix}.change-visibility"),
            'destroy' => route("{$prefix}.destroy"),
            'convert' => str_replace('__id__', ':id', route("{$prefix}.convert", [$parameterName => '__id__'])),
        ];
    }

    public function convert(Request $request, HiringLead $hiringLead): RedirectResponse|JsonResponse
    {
        $this->authorizeAccess(AccessAction::UPDATE);

        $result = $this->convertHiringLead->execute($hiringLead);

        if (! $result->success) {
            return $this->actionFailureResponse($request, $result->errors, $result->message);
        }

        if ($request->expectsJson()) {
            return response()->json($result->data);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $result->message,
        ]);

        return redirect()->route($this->routePrefix().'.index');
    }

    /**
     * @param  array<string, mixed>|null  $errors
     */
    private function actionFailureResponse(Request $request, ?array $errors, ?string $message): RedirectResponse|JsonResponse
    {
        $message ??= 'Could not complete the operation.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'errors' => $errors,
            ], 422);
        }

        return back()->withErrors($errors ?? ['action' => $message])->withInput();
    }

    /**
     * @return array<string, mixed>
     */
    protected function moduleIndexProps(Request $request): array
    {
        return [
            'options' => [
                'hiringLeadStatus' => $this->enumOptions(HiringLeadStatus::class),
                'hiringLeadSource' => $this->enumOptions(HiringLeadSource::class),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function moduleDetailsProps(?Model $model = null): array
    {
        return [
            'options' => [
                'hiringLeadStatus' => $this->enumOptions(HiringLeadStatus::class),
                'hiringLeadSource' => $this->enumOptions(HiringLeadSource::class),
                'genderTypes' => $this->enumOptions(GenderType::class),
                'states' => $this->modelOptions(State::class),
                'plans' => Plan::query()
                    ->select(['id', 'name'])
                    ->orderBy('name')
                    ->get()
                    ->map(fn (Plan $plan): array => [
                        'value' => (string) $plan->getKey(),
                        'label' => $plan->name,
                    ])
                    ->all(),
                'coupons' => Coupon::query()
                    ->select(['id', 'code'])
                    ->orderBy('code')
                    ->get()
                    ->map(fn (Coupon $coupon): array => [
                        'value' => (string) $coupon->getKey(),
                        'label' => $coupon->code,
                    ])
                    ->all(),
            ],
        ];
    }
}
