<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessAction;
use App\AccessControl\AccessModule;
use App\Actions\Modalities\CreateModalityAction;
use App\Actions\Modalities\UpdateModalityAction;
use App\DTOs\Modalities\CreateModalityDTO;
use App\DTOs\Modalities\UpdateModalityDTO;
use App\Models\Modality;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ModalityController extends CrudModuleController
{
    public function __construct(
        private readonly CreateModalityAction $createModality,
        private readonly UpdateModalityAction $updateModality,
    ) {}

    /**
     * @var array<int, string>
     */
    protected array $fields = ['id', 'name', 'color', 'created_at'];

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
        return AccessModule::MODALITY;
    }

    protected function modelClass(): string
    {
        return Modality::class;
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeAccess(AccessAction::CREATE);

        $validated = $request->validate($this->graduationRules());

        $result = $this->createModality->execute(
            CreateModalityDTO::from([
                'name' => $validated['name'],
                'color' => $validated['color'] ?? null,
                'graduations' => $validated['graduations'] ?? [],
            ])
        );

        if ($request->expectsJson()) {
            return response()->json($result->data, 201);
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

        /** @var Modality $modality */
        $modality = $this->modelFromRoute($request);

        $validated = $request->validate($this->graduationRules());

        $result = $this->updateModality->execute(
            UpdateModalityDTO::from([
                'id' => $modality->getKey(),
                'name' => $validated['name'],
                'color' => $validated['color'] ?? null,
                'graduations' => $validated['graduations'] ?? [],
            ])
        );

        if ($request->expectsJson()) {
            return response()->json($result->data);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __($this->accessModule()->label().' atualizado com sucesso.'),
        ]);

        return redirect()->route($this->routePrefix().'.index');
    }

    /**
     * @return array<string, mixed>
     */
    protected function moduleDetailsProps(?Model $model = null): array
    {
        return [
            'graduations' => $model instanceof Modality
                ? $model->graduations()->orderBy('id')->get(['id', 'name', 'color'])->all()
                : [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function graduationRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'graduations' => ['nullable', 'array'],
            'graduations.*.id' => ['nullable', 'integer'],
            'graduations.*.name' => ['required', 'string', 'max:255'],
            'graduations.*.color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }
}
