<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessAction;
use App\AccessControl\AccessModule;
use App\Actions\FinancialAccounts\CreateFinancialAccountAction;
use App\Actions\FinancialAccounts\UpdateFinancialAccountAction;
use App\DTOs\FinancialAccounts\CreateFinancialAccountDTO;
use App\DTOs\FinancialAccounts\UpdateFinancialAccountDTO;
use App\Enums\FinancialAccountType;
use App\Http\Requests\FinancialAccountRequest;
use App\Models\FinancialAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FinancialAccountController extends CrudModuleController
{
    public function __construct(
        private readonly CreateFinancialAccountAction $createFinancialAccount,
        private readonly UpdateFinancialAccountAction $updateFinancialAccount,
    ) {}

    /**
     * @var array<int, string>
     */
    protected array $fields = ['id', 'name', 'account_type', 'balance', 'created_at'];

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
        return AccessModule::FINANCIAL_ACCOUNT;
    }

    protected function modelClass(): string
    {
        return FinancialAccount::class;
    }

    protected function storeRequestClass(): ?string
    {
        return FinancialAccountRequest::class;
    }

    protected function updateRequestClass(): ?string
    {
        return FinancialAccountRequest::class;
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeAccess(AccessAction::CREATE);

        $result = $this->createFinancialAccount->execute(
            CreateFinancialAccountDTO::from($this->validatedRequestData($request, $this->storeRequestClass()))
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

        /** @var FinancialAccount $financialAccount */
        $financialAccount = $this->modelFromRoute($request);

        $result = $this->updateFinancialAccount->execute(
            UpdateFinancialAccountDTO::from([
                ...$this->validatedRequestData($request, $this->updateRequestClass()),
                'id' => $financialAccount->getKey(),
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

    protected function moduleIndexProps(Request $request): array
    {
        return [
            'options' => [
                'accountTypes' => $this->enumOptions(FinancialAccountType::class),
            ],
        ];
    }

    protected function moduleDetailsProps(?Model $model = null): array
    {
        return [
            'options' => [
                'accountTypes' => $this->enumOptions(FinancialAccountType::class),
            ],
        ];
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
