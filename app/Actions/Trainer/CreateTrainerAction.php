<?php

namespace App\Actions\Trainer;

use App\Actions\BaseAction;
use App\DTOs\Trainer\ActionResultDTO;
use App\DTOs\Trainer\CreateTrainerDTO;
use App\Models\Trainer;
use App\Repositories\Contracts\TrainerRepositoryInterface;

class CreateTrainerAction extends BaseAction
{
    /** Module access is enforced by the HTTP controller's permission check. */
    protected string $ability = '';

    protected string $modelClass = Trainer::class;

    public function __construct(
        private readonly TrainerRepositoryInterface $trainerRepository,
    ) {}

    protected function handle(mixed $input): ActionResultDTO
    {
        if (! $input instanceof CreateTrainerDTO) {
            throw new \InvalidArgumentException('CreateTrainerAction requires a CreateTrainerDTO.');
        }

        $dto = $input;
        $trainer = $this->trainerRepository->create([
            'name' => $dto->name,
            'email' => $dto->email,
            'document' => $dto->document,
            'birth_date' => $dto->birth_date,
            'phone' => $dto->phone,
            'gender' => $dto->gender,
            'profile_image' => $dto->profile_image,
            'address' => $dto->address,
            'address_number' => $dto->address_number,
            'address_complement' => $dto->address_complement,
            'address_state' => $dto->address_state,
            'address_city' => $dto->address_city,
            'address_district' => $dto->address_district,
            'address_postal_code' => $dto->address_postal_code,
        ]);

        $trainer->modalities()->createMany(array_map(
            fn (int $modalityId): array => ['modality_id' => $modalityId],
            $dto->trainer_modalities,
        ));

        return ActionResultDTO::success(
            $trainer->refresh()->load('modalities'),
            'Instrutor criado com sucesso.'
        );
    }
}
