<?php

namespace App\Actions\LoyaltyLevels;

use App\Actions\BaseAction;
use App\DTOs\LoyaltyLevels\ActionResultDTO;
use App\DTOs\LoyaltyLevels\CreateLoyaltyLevelDTO;
use App\Models\LoyaltyLevel;
use App\Repositories\Contracts\LoyaltyLevelRepositoryInterface;

class CreateLoyaltyLevelAction extends BaseAction
{
    protected string $ability = '';

    protected string $modelClass = LoyaltyLevel::class;

    public function __construct(
        private readonly LoyaltyLevelRepositoryInterface $loyaltyLevelRepository,
    ) {}

    protected function handle(mixed $input): ActionResultDTO
    {
        if (! $input instanceof CreateLoyaltyLevelDTO) {
            throw new \InvalidArgumentException('CreateLoyaltyLevelAction requires a CreateLoyaltyLevelDTO.');
        }

        $dto = $input;

        $level = $this->loyaltyLevelRepository->create([
            'name' => $dto->name,
            'min_months' => $dto->min_months,
            'color' => $dto->color,
            'description' => $dto->description,
        ]);

        return ActionResultDTO::success(
            $level,
            'Nível de fidelidade criado com sucesso.'
        );
    }
}
