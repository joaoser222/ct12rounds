<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContractWizardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'plan_id' => [
                'required',
                'integer',
                Rule::exists('plans', 'id')->where(
                    fn (Builder $query): Builder => $query->where('visibility', 'visible')
                ),
            ],
            'installments' => [
                'required',
                'integer',
                'min:1',
                Rule::exists('plan_tiers', 'quantity')->where(
                    fn (Builder $query): Builder => $query->where('plan_id', $this->integer('plan_id'))
                ),
            ],
            'coupon_id' => ['nullable', 'integer', 'exists:coupons,id'],
            'annotations' => ['nullable', 'string', 'max:500'],
        ];
    }
}