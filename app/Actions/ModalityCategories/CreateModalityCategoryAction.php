<?php

namespace App\Actions\ModalityCategories;

use App\Actions\BaseAction;
use App\DTOs\ModalityCategories\ActionResultDTO;
use App\DTOs\ModalityCategories\CreateModalityCategoryDTO;
use App\Models\ModalityCategory;
use App\Repositories\Contracts\ModalityCategoryRepositoryInterface;

class CreateModalityCategoryAction extends BaseAction
{
    protected string $ability = '';

    protected string $modelClass = ModalityCategory::class;

    public function __construct(
        private readonly ModalityCategoryRepositoryInterface $modalityCategoryRepository,
    ) {}

    protected function handle(mixed $input): ActionResultDTO
    {
        if (! $input instanceof CreateModalityCategoryDTO) {
            throw new \InvalidArgumentException('CreateModalityCategoryAction requires a CreateModalityCategoryDTO.');
        }

        $dto = $input;

        $modalityCategory = $this->modalityCategoryRepository->create([
            'name' => $dto->name,
            'slug' => $dto->slug,
            'audience' => $dto->audience,
        ]);

        return ActionResultDTO::success(
            $modalityCategory,
            'Categoria de modalidade criada com sucesso.'
        );
    }
}
