<?php

namespace App\Actions\Modalities;

use App\Actions\BaseAction;
use App\DTOs\Modalities\ActionResultDTO;
use App\DTOs\Modalities\UpdateModalityDTO;
use App\Models\Modality;
use App\Repositories\Contracts\ModalityRepositoryInterface;
use App\Services\GraduationService;

class UpdateModalityAction extends BaseAction
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
        if (! $input instanceof UpdateModalityDTO) {
            throw new \InvalidArgumentException('UpdateModalityAction requires an UpdateModalityDTO.');
        }

        $dto = $input;
        $modality = $this->modalityRepository->findOrFail($dto->id);

        $this->modalityRepository->update($modality, [
            'name' => $dto->name,
            'color' => $dto->color,
        ]);

        $this->graduationService->syncModalityGraduations($modality, $dto->graduations);

        return ActionResultDTO::success(
            $modality->refresh()->load('graduations'),
            'Modalidade atualizada com sucesso.'
        );
    }
}
