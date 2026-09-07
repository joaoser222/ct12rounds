<?php

namespace App\Actions\Plans;

use App\Actions\BaseAction;
use App\DTOs\Plans\ActionResultDTO;
use App\DTOs\Plans\UpdatePlanDTO;
use App\Models\Plan;
use App\Repositories\Contracts\PlanRepositoryInterface;

class UpdatePlanAction extends BaseAction
{
    /** Module access is enforced by the HTTP controller's permission check. */
    protected string $ability = '';

    protected string $modelClass = Plan::class;

    public function __construct(
        private readonly PlanRepositoryInterface $planRepository,
    ) {}

    protected function handle(mixed $input): ActionResultDTO
    {
        if (! $input instanceof UpdatePlanDTO) {
            throw new \InvalidArgumentException('UpdatePlanAction requires an UpdatePlanDTO.');
        }

        $dto = $input;
        $plan = $this->planRepository->findOrFail($dto->id);

        $this->planRepository->update($plan, [
            'name' => $dto->name,
            'description' => $dto->description,
            'price' => $dto->price,
            'duration_months' => $dto->duration_months,
            'plan_category_id' => $dto->plan_category_id,
        ]);

        $plan->modalities()->delete();
        $plan->modalities()->createMany(array_map(
            fn (int $modalityId): array => ['modality_id' => $modalityId],
            $dto->plan_modalities,
        ));

        return ActionResultDTO::success(
            $plan->refresh()->load('modalities'),
            'Plano atualizado com sucesso.'
        );
    }
}
