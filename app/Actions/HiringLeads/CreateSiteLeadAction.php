<?php

namespace App\Actions\HiringLeads;

use App\Actions\BaseAction;
use App\Enums\HiringLeadSource;
use App\Enums\HiringLeadStatus;
use App\Models\HiringLead;
use Illuminate\Support\Carbon;

class CreateSiteLeadAction extends BaseAction
{
    protected string $ability = '';

    protected string $modelClass = HiringLead::class;

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
            'phone' => $input['phone'],
            'document' => preg_replace('/\D/', '', (string) $input['document']),
            'status' => HiringLeadStatus::NEW->value,
            'source' => HiringLeadSource::SITE->value,
            'visibility' => 'visible',
            'accepted_at' => Carbon::now(),
        ]);
    }
}