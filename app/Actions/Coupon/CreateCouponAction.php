<?php

namespace App\Actions\Coupon;

use App\Actions\BaseAction;
use App\DTOs\Coupon\ActionResultDTO;
use App\DTOs\Coupon\CreateCouponDTO;
use App\Models\Coupon;
use App\Repositories\Contracts\CouponRepositoryInterface;

class CreateCouponAction extends BaseAction
{
    /** Module access is enforced by the HTTP controller's permission check. */
    protected string $ability = '';

    protected string $modelClass = Coupon::class;

    public function __construct(
        private readonly CouponRepositoryInterface $couponRepository,
    ) {}

    protected function handle(mixed $input): ActionResultDTO
    {
        if (! $input instanceof CreateCouponDTO) {
            throw new \InvalidArgumentException('CreateCouponAction requires a CreateCouponDTO.');
        }

        $dto = $input;
        $coupon = $this->couponRepository->create([
            'code' => $dto->code,
            'percent' => $dto->percent,
            'discount_limit' => $dto->discount_limit,
            'duration' => $dto->duration,
            'expiration_date' => $dto->expiration_date,
        ]);

        $coupon->plans()->sync($dto->plan_ids);

        return ActionResultDTO::success(
            $coupon->refresh()->load('plans'),
            'Cupom criado com sucesso.'
        );
    }
}
