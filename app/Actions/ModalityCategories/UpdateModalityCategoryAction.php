<?php

namespace App\Actions\ModalityCategories;

use App\Actions\BaseAction;
use App\DTOs\ModalityCategories\ActionResultDTO;
use App\DTOs\ModalityCategories\UpdateModalityCategoryDTO;
use App\Models\ModalityCategory;
use App\Repositories\Contracts\ModalityCategoryRepositoryInterface;

class UpdateModalityCategoryAction extends BaseAction
{
    protected string $ability = '';

    protected string $modelClass = ModalityCategory::class;

    public function __construct(
        private readonly ModalityCategoryRepositoryInterface $modalityCategoryRepository,
    ) {}

    protected function handle(mixed $input): ActionResultDTO
    {
        if (! $input instanceof UpdateModalityCategoryDTO) {
            throw new \InvalidArgumentException('UpdateModalityCategoryAction requires an UpdateModalityCategoryDTO.');
        }

        $dto = $input;

        /** @var ModalityCategory $modalityCategory */
        $modalityCategory = $this->modalityCategoryRepository->findOrFail($dto->id);

        $modalityCategory = $this->modalityCategoryRepository->update($modalityCategory, array_filter([
            'name' => $dto->name,
            'slug' => $dto->slug,
            'audience' => $dto->audience,
        ], fn ($v) => $v !== null));

        return ActionResultDTO::success(
            $modalityCategory,
            'Categoria de modalidade atualizada com sucesso.'
        );
    }
}
