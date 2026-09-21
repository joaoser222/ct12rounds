<?php

namespace App\Actions\Clients;

use App\Actions\BaseAction;
use App\DTOs\Clients\ActionResultDTO;
use App\DTOs\Clients\ClientResultDTO;
use App\DTOs\Clients\CreateClientDTO;
use App\Models\Client;
use App\Repositories\Contracts\ClientRepositoryInterface;

class CreateClientAction extends BaseAction
{
    /** Module access is enforced by the HTTP controller's permission check. */
    protected string $ability = '';

    protected string $modelClass = Client::class;

    public function __construct(
        private readonly ClientRepositoryInterface $clientRepository,
    ) {}

    protected function handle(mixed $input): ActionResultDTO
    {
        if (! $input instanceof CreateClientDTO) {
            throw new \InvalidArgumentException('CreateClientAction requires a CreateClientDTO.');
        }

        $dto = $input;

        $client = $this->clientRepository->create([
            'name' => $dto->name,
            'email' => $dto->email,
            'phone' => $dto->phone,
            'document' => $dto->document,
            'gender' => $dto->gender,
            'birth_date' => $dto->birth_date,
            'audience_category' => $dto->audience_category?->value,
            'legal_representative_name' => $dto->legal_representative_name,
            'legal_representative_document' => $dto->legal_representative_document,
            'legal_representative_birth_date' => $dto->legal_representative_birth_date,
            'address_postal_code' => $dto->address_postal_code,
            'address' => $dto->address,
            'address_number' => $dto->address_number,
            'address_complement' => $dto->address_complement,
            'address_district' => $dto->address_district,
            'address_state' => $dto->address_state,
            'address_city' => $dto->address_city,
            'status' => 'active',
        ]);

        return ActionResultDTO::success(
            ClientResultDTO::fromModel($client),
            'Cliente criado com sucesso.'
        );
    }
}
