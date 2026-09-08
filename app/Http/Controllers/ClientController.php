<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessAction;
use App\AccessControl\AccessModule;
use App\Actions\Clients\CreateClientAction;
use App\Actions\Clients\UpdateClientAction;
use App\DTOs\Clients\CreateClientDTO;
use App\DTOs\Clients\UpdateClientDTO;
use App\Enums\ClientStatus;
use App\Enums\GenderType;
use App\Http\Requests\ClientRequest;
use App\Models\Client;
use App\Models\State;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClientController extends CrudModuleController
{
    public function __construct(
        private readonly CreateClientAction $createClient,
        private readonly UpdateClientAction $updateClient,
    ) {}

    /**
     * @var array<int, string>
     */
    protected array $fields = ['id', 'name', 'document', 'status', 'phone', 'created_at', 'updated_at'];

    /**
     * @var array<int, string>
     */
    protected array $searchableFields = ['name', 'email', 'document', 'phone'];

    /**
     * @var array<int, string>
     */
    protected array $sortableFields = ['id', 'created_at', 'updated_at'];

    protected function accessModule(): AccessModule
    {
        return AccessModule::CLIENT;
    }

    protected function modelClass(): string
    {
        return Client::class;
    }

    protected function storeRequestClass(): ?string
    {
        return ClientRequest::class;
    }

    protected function updateRequestClass(): ?string
    {
        return ClientRequest::class;
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeAccess(AccessAction::CREATE);

        $result = $this->createClient->execute(
            CreateClientDTO::from(
                $this->validatedRequestData($request, $this->storeRequestClass())
            )
        );

        if (! $result->success) {
            return $this->actionFailureResponse($request, $result->errors, $result->message);
        }

        if ($request->expectsJson()) {
            return response()->json($result->data, 201);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $result->message,
        ]);

        return redirect()->route($this->routePrefix().'.index');
    }

    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeAccess(AccessAction::UPDATE);

        $model = $this->modelFromRoute($request);

        $result = $this->updateClient->execute(
            UpdateClientDTO::from([
                ...$this->validatedRequestData($request, $this->updateRequestClass()),
                'id' => $model->getKey(),
            ])
        );

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
        $message ??= 'Não foi possível concluir a operação.';

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
    protected function moduleDetailsProps(?Model $model = null): array
    {
        return [
            'options' => [
                'genderTypes' => $this->enumOptions(GenderType::class),
                'states' => $this->modelOptions(State::class),
            ],

        ];
    }

    protected function moduleIndexProps(Request $request): array
    {
        return [
            'options' => [
                'clientStatus' => $this->enumOptions(ClientStatus::class),
            ],

        ];
    }
}
