<?php

namespace App\Actions\LoyaltyLevels;

use App\Actions\BaseAction;
use App\DTOs\LoyaltyLevels\ActionResultDTO;
use App\DTOs\LoyaltyLevels\UpdateLoyaltyLevelDTO;
use App\Models\LoyaltyLevel;
use App\Repositories\Contracts\LoyaltyLevelRepositoryInterface;

class UpdateLoyaltyLevelAction extends BaseAction
{
    protected string $ability = '';

    protected string $modelClass = LoyaltyLevel::class;

    public function __construct(
        private readonly LoyaltyLevelRepositoryInterface $loyaltyLevelRepository,
    ) {}

    protected function handle(mixed $input): ActionResultDTO
    {
        if (! $input instanceof UpdateLoyaltyLevelDTO) {
            throw new \InvalidArgumentException('UpdateLoyaltyLevelAction requires an UpdateLoyaltyLevelDTO.');
        }

        $dto = $input;

        /** @var LoyaltyLevel $level */
        $level = $this->loyaltyLevelRepository->findOrFail($dto->id);

        $level = $this->loyaltyLevelRepository->update($level, array_filter([
            'name' => $dto->name,
            'min_months' => $dto->min_months,
            'color' => $dto->color,
            'description' => $dto->description,
        ], fn ($v) => $v !== null));

        return ActionResultDTO::success(
            $level,
            'Nível de fidelidade atualizado com sucesso.'
        );
    }
}
