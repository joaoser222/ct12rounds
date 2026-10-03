<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessAction;
use App\AccessControl\AccessModule;
use App\Actions\Clients\CreateClientAction;
use App\Actions\Clients\UpdateClientAction;
use App\DTOs\Clients\ClientImageRightsData;
use App\DTOs\Clients\CreateClientDTO;
use App\DTOs\Clients\UpdateClientDTO;
use App\Enums\ClientStatus;
use App\Enums\GenderType;
use App\Http\Requests\ClientRequest;
use App\Models\Client;
use App\Models\ClientGraduation;
use App\Models\LoyaltyLevel;
use App\Models\ModalityGraduation;
use App\Models\State;
use App\Services\PrintableReportService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
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
    protected array $fields = ['id', 'name', 'document', 'status', 'phone', 'loyalty_streak_months', 'created_at', 'updated_at'];

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

    /**
     * @return array<string, string>
     */
    protected function getModuleRoutes(): array
    {
        $routes = parent::getModuleRoutes();
        $imageRightsRoute = route('clients.image-rights', ['client' => '__id__']);

        return [
            ...$routes,
            'imageRights' => str_replace('__id__', ':id', $imageRightsRoute),
        ];
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

    public function imageRights(Request $request, Client $client, PrintableReportService $reportService): Response
    {
        $this->authorizeAccess(AccessAction::VIEW);

        $data = $request->validate([
            'image_producer_name' => ['nullable', 'string', 'max:255'],
            'image_usage_purpose' => ['nullable', 'string', 'max:1000'],
            'image_description' => ['nullable', 'string', 'max:1000'],
            'image_material_type' => ['nullable', 'string', 'max:255'],
            'site_owner_name' => ['nullable', 'string', 'max:255'],
            'site_name' => ['nullable', 'string', 'max:255'],
            'site_domain' => ['nullable', 'string', 'max:255'],
            'forum_city' => ['nullable', 'string', 'max:120'],
            'legal_representative_relationship' => ['nullable', 'string', 'max:120'],
        ]);

        return $reportService->pdf(
            template: 'templates/image_rights.blade.php',
            data: ClientImageRightsData::from($client, $data)->toArray(),
            filename: 'autorizacao-de-imagem-'.(Str::slug($client->name) ?: 'cliente').'.pdf',
            title: 'Autorização de Uso e Cessão de Direitos de Imagem',
        );
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
            'imageRightsDefaults' => [
                'image_producer_name' => (string) config('app.name'),
                'image_usage_purpose' => 'divulgação institucional e promocional',
                'image_description' => 'imagem do cliente',
                'image_material_type' => 'fotografia e/ou filmagem',
                'site_owner_name' => (string) config('app.name'),
                'site_name' => (string) config('app.name'),
                'site_domain' => parse_url((string) config('app.url'), PHP_URL_HOST) ?: '',
                'forum_city' => $model instanceof Client ? $model->address_city : '',
                'legal_representative_relationship' => 'responsável legal',
            ],
            'graduations' => $this->clientGraduationRows($model),
            'graduationOptions' => $this->graduationOptions(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function clientGraduationRows(?Model $model): array
    {
        if (! $model instanceof Client) {
            return [];
        }

        return $model->graduations()
            ->with('modalityGraduation:id,name,modality_id')
            ->orderBy('id')
            ->get()
            ->map(fn (ClientGraduation $graduation): array => [
                'modality_id' => (int) $graduation->modalityGraduation->modality_id,
                'modality_graduation_id' => (int) $graduation->modality_graduation_id,
                'promoted_at' => $graduation->promoted_at?->format('Y-m-d'),
            ])
            ->all();
    }

    /**
     * Modalities and their graduations, used by the dependent selects on the client tab.
     *
     * @return array<int, array<string, mixed>>
     */
    private function graduationOptions(): array
    {
        return ModalityGraduation::query()
            ->whereHas('modality', fn (Builder $query) => $query->where('visibility', 'visible'))
            ->with('modality:id,name')
            ->orderBy('modality_id')
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->map(fn (ModalityGraduation $graduation): array => [
                'value' => (string) $graduation->getKey(),
                'label' => $graduation->name,
                'color' => $graduation->color,
                'modality_id' => (string) $graduation->modality_id,
                'modality_name' => $graduation->modality->name,
                'position' => (int) $graduation->position,
            ])
            ->all();
    }

    protected function moduleIndexProps(Request $request): array
    {
        return [
            'options' => [
                'clientStatus' => $this->enumOptions(ClientStatus::class),
                'loyaltyLevels' => LoyaltyLevel::query()
                    ->select(['id', 'name', 'color'])
                    ->orderBy('name')
                    ->get()
                    ->map(fn (LoyaltyLevel $level): array => [
                        'value' => (string) $level->getKey(),
                        'label' => $level->name,
                        'color' => $level->color,
                    ])
                    ->all(),
            ],
        ];
    }
}
