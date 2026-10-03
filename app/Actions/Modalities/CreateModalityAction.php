<?php

namespace App\Actions\Modalities;

use App\Actions\BaseAction;
use App\DTOs\Modalities\ActionResultDTO;
use App\DTOs\Modalities\CreateModalityDTO;
use App\Models\Modality;
use App\Repositories\Contracts\ModalityRepositoryInterface;
use App\Services\GraduationService;

class CreateModalityAction extends BaseAction
{
    /** Module access is enforced by the HTTP controller's permission check. */
    protected string $ability = '';

    protected string $modelClass = Modality::class;

    public function __construct(
        private readonly ModalityRepositoryInterface $modalityRepository,
        private readonly GraduationService $graduationService,
    ) {}

    protected function handle(mixed $input): ActionResultDTO
    {
        if (! $input instanceof CreateModalityDTO) {
            throw new \InvalidArgumentException('CreateModalityAction requires a CreateModalityDTO.');
        }

        $dto = $input;
        $modality = $this->modalityRepository->create([
            'name' => $dto->name,
            'color' => $dto->color,
        ]);

        $this->graduationService->syncModalityGraduations($modality, $dto->graduations);

        return ActionResultDTO::success(
            $modality->refresh()->load('graduations'),
            'Modalidade criada com sucesso.'
        );
    }
}
