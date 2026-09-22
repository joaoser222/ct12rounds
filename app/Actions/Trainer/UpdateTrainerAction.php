<?php

namespace App\Actions\Trainer;

use App\Actions\BaseAction;
use App\DTOs\Trainer\ActionResultDTO;
use App\DTOs\Trainer\UpdateTrainerDTO;
use App\Models\Trainer;
use App\Repositories\Contracts\TrainerRepositoryInterface;

class UpdateTrainerAction extends BaseAction
{
    /** Module access is enforced by the HTTP controller's permission check. */
    protected string $ability = '';

    protected string $modelClass = Trainer::class;

    public function __construct(
        private readonly TrainerRepositoryInterface $trainerRepository,
    ) {}

    protected function handle(mixed $input): ActionResultDTO
    {
        if (! $input instanceof UpdateTrainerDTO) {
            throw new \InvalidArgumentException('UpdateTrainerAction requires an UpdateTrainerDTO.');
        }

        $dto = $input;
        $trainer = $this->trainerRepository->findOrFail($dto->id);

        $updateData = array_filter([
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
        ], fn ($value) => $value !== null);

        $this->trainerRepository->update($trainer, $updateData);

        $trainer->modalities()->delete();
        $trainer->modalities()->createMany(array_map(
            fn (int $modalityId): array => ['modality_id' => $modalityId],
            $dto->trainer_modalities,
        ));

        return ActionResultDTO::success(
            $trainer->refresh()->load('modalities'),
            'Instrutor atualizado com sucesso.'
        );
    }
}
