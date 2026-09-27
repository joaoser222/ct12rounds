<?php

namespace App\Actions\Coupon;

use App\Actions\BaseAction;
use App\DTOs\Coupon\ActionResultDTO;
use App\DTOs\Coupon\UpdateCouponDTO;
use App\Models\Coupon;
use App\Repositories\Contracts\CouponRepositoryInterface;

class UpdateCouponAction extends BaseAction
{
    /** Module access is enforced by the HTTP controller's permission check. */
    protected string $ability = '';

    protected string $modelClass = Coupon::class;

    public function __construct(
        private readonly CouponRepositoryInterface $couponRepository,
    ) {}

    protected function handle(mixed $input): ActionResultDTO
    {
        if (! $input instanceof UpdateCouponDTO) {
            throw new \InvalidArgumentException('UpdateCouponAction requires an UpdateCouponDTO.');
        }

        $dto = $input;
        $coupon = $this->couponRepository->findOrFail($dto->id);

        $updateData = array_filter([
            'code' => $dto->code,
            'percent' => $dto->percent,
            'discount_limit' => $dto->discount_limit,
            'duration' => $dto->duration,
            'expiration_date' => $dto->expiration_date,
        ], fn ($value) => $value !== null);

        $this->couponRepository->update($coupon, $updateData);

        if ($dto->plan_ids !== null) {
            $coupon->plans()->sync($dto->plan_ids);
        }

        return ActionResultDTO::success(
            $coupon->refresh()->load('plans'),
            'Cupom atualizado com sucesso.'
        );
    }
}
