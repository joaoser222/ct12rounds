<?php

namespace App\Actions\HiringLeads;

use App\Actions\BaseAction;
use App\Enums\HiringLeadSource;
use App\Enums\HiringLeadStatus;
use App\Models\HiringLead;
use App\Services\ContactNormalizer;
use Illuminate\Support\Carbon;

class CreateSiteLeadAction extends BaseAction
{
    protected string $ability = '';

    protected string $modelClass = HiringLead::class;

    public function __construct(
        private readonly ContactNormalizer $contactNormalizer,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    protected function handle(mixed $input): HiringLead
    {
        if (! is_array($input)) {
            throw new \InvalidArgumentException('CreateSiteLeadAction expects an array of validated data.');
        }

        return HiringLead::query()->create([
            'name' => $input['name'],
            'email' => $input['email'],
            'phone' => $this->contactNormalizer->phone($input['phone']),
            'document' => $this->contactNormalizer->document($input['document'] ?? null),
            'status' => HiringLeadStatus::NEW->value,
            'source' => HiringLeadSource::SITE->value,
            'visibility' => 'visible',
            'plan_id' => $input['plan_id'] ?? null,
            'coupon_id' => $input['coupon_id'] ?? null,
            'accepted_at' => Carbon::now(),
        ]);
    }
}