<?php

namespace App\Actions\Contracts;

use App\Actions\BaseAction;
use App\DTOs\Contracts\ActionResultDTO;
use App\DTOs\Contracts\ContractResultDTO;
use App\DTOs\Contracts\CreateContractDTO;
use App\Models\Contract;
use App\Models\PlanTier;
use App\Repositories\Contracts\ContractRepositoryInterface;
use App\Repositories\Contracts\CouponRepositoryInterface;
use App\Repositories\Contracts\PlanRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

class CreateContractAction extends BaseAction
{
    /** Module access is enforced by the HTTP controller's permission check. */
    protected string $ability = '';

    protected string $modelClass = Contract::class;

    public function __construct(
        private readonly ContractRepositoryInterface $contractRepository,
        private readonly PlanRepositoryInterface $planRepository,
        private readonly CouponRepositoryInterface $couponRepository,
    ) {}

    protected function handle(mixed $input): ActionResultDTO
    {
        if (! $input instanceof CreateContractDTO) {
            throw new \InvalidArgumentException('CreateContractAction requires a CreateContractDTO.');
        }

        $dto = $input;

        $plan = $this->planRepository->newQuery()
            ->with(['tiers', 'planCategory'])
            ->where('visibility', 'visible')
            ->whereKey($dto->plan_id)
            ->firstOrFail();

        /** @var PlanTier|null $tier */
        $tier = $plan->tiers->firstWhere('quantity', $dto->installments);

        if ($tier === null) {
            return ActionResultDTO::failure(
                'A duração selecionada não está disponível para este plano.',
                ['installments' => 'A duração selecionada não está disponível para este plano.']
            );
        }

        $coupon = null;

        if ($dto->coupon_id !== null) {
            $coupon = $this->couponRepository->newQuery()
                ->where('visibility', 'visible')
                ->whereKey($dto->coupon_id)
                ->first();

            if ($coupon === null) {
                return ActionResultDTO::failure(
                    'O cupom informado não está disponível.',
                    ['coupon_id' => 'O cupom informado não está disponível.']
                );
            }

            if ($coupon->expiration_date !== null && $coupon->expiration_date->isBefore(CarbonImmutable::today())) {
                return ActionResultDTO::failure(
                    'O cupom informado está expirado.',
                    ['coupon_id' => 'O cupom informado está expirado.']
                );
            }
        }

        $grossValue = round((float) $tier->price * (int) $dto->installments, 4);

        $contract = $this->contractRepository->create([
            'plan_name' => $plan->name,
            'modality_quantity' => (string) $plan->modality_quantity,
            'gross_value' => $grossValue,
            'discount_value' => 0,
            'total' => $grossValue,
            'payment_method' => 'cash',
            'first_due_date' => CarbonImmutable::today()->format('Y-m-d'),
            'installments' => $dto->installments,
            'accepted_terms' => 'pending',
            'annotations' => $dto->annotations,
            'coupon_id' => $coupon?->id,
            'plan_id' => $plan->id,
            'registration_token' => Str::random(64),
            'visibility' => 'visible',
        ]);

        return ActionResultDTO::success(
            ContractResultDTO::fromModel($contract->refresh()),
            'Contrato criado com sucesso. Compartilhe o QR Code para o cadastro do cliente.'
        );
    }
}