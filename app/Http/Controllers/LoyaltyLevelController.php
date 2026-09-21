<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessAction;
use App\AccessControl\AccessModule;
use App\Actions\LoyaltyLevels\CreateLoyaltyLevelAction;
use App\Actions\LoyaltyLevels\UpdateLoyaltyLevelAction;
use App\DTOs\LoyaltyLevels\CreateLoyaltyLevelDTO;
use App\DTOs\LoyaltyLevels\UpdateLoyaltyLevelDTO;
use App\Models\LoyaltyLevel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LoyaltyLevelController extends CrudModuleController
{
    public function __construct(
        private readonly CreateLoyaltyLevelAction $createLoyaltyLevel,
        private readonly UpdateLoyaltyLevelAction $updateLoyaltyLevel,
    ) {}

    /**
     * @var array<int, string>
     */
    protected array $fields = ['id', 'name', 'min_months', 'color', 'created_at'];

    /**
     * @var array<int, string>
     */
    protected array $searchableFields = ['name'];

    /**
     * @var array<int, string>
     */
    protected array $sortableFields = ['id', 'name', 'min_months', 'created_at'];

    protected function accessModule(): AccessModule
    {
        return AccessModule::LOYALTY_LEVEL;
    }

    protected function modelClass(): string
    {
        return LoyaltyLevel::class;
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeAccess(AccessAction::CREATE);

        $result = $this->createLoyaltyLevel->execute(
            CreateLoyaltyLevelDTO::from($request->validate([
                'name' => ['required', 'string', 'max:100'],
                'min_months' => ['required', 'integer', 'min:0'],
                'color' => ['nullable', 'string', 'max:7'],
                'description' => ['nullable', 'string', 'max:500'],
            ]))
        );

        if (! $result->success) {
            return $this->actionFailureResponse($request, $result->errors, $result->message);
        }

        if ($request->expectsJson()) {
            return response()->json($result->data, 201);
        }

        return redirect()->route($this->routePrefix().'.index');
    }

    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeAccess(AccessAction::UPDATE);

        /** @var LoyaltyLevel $loyaltyLevel */
        $loyaltyLevel = $this->modelFromRoute($request);

        $result = $this->updateLoyaltyLevel->execute(
            UpdateLoyaltyLevelDTO::from([
                ...$request->validate([
                    'name' => ['nullable', 'string', 'max:100'],
                    'min_months' => ['nullable', 'integer', 'min:0'],
                    'color' => ['nullable', 'string', 'max:7'],
                    'description' => ['nullable', 'string', 'max:500'],
                ]),
                'id' => $loyaltyLevel->getKey(),
            ])
        );

        if (! $result->success) {
            return $this->actionFailureResponse($request, $result->errors, $result->message);
        }

        if ($request->expectsJson()) {
            return response()->json($result->data);
        }

        return redirect()->route($this->routePrefix().'.index');
    }

    private function actionFailureResponse(Request $request, ?array $errors, ?string $message): RedirectResponse|JsonResponse
    {
        $message ??= 'Não foi possível concluir a operação.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'errors' => $errors,
            ], 422);
        }

        return back()->withErrors($errors ?? ['action' => $message])->withInput();
    }
}
