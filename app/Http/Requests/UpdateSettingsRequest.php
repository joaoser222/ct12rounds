<?php

namespace App\Http\Requests;

use App\Models\Setting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingsRequest extends FormRequest
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
        $rules = [
            'settings' => ['required', 'array'],
        ];

        Setting::query()
            ->select(['name', 'object_type'])
            ->orderBy('id')
            ->get()
            ->each(function (Setting $setting) use (&$rules): void {
                $rules['settings.'.$setting->name] = $this->rulesForSetting($setting);
            });

        return $rules;
    }

    /**
     * @return array<int, ValidationRule|string>
     */
    private function rulesForSetting(Setting $setting): array
    {
        if ($setting->isStaticSelection()) {
            return ['nullable', 'string', Rule::in(collect($setting->options())->pluck('value')->all())];
        }

        $selectTable = $setting->selectTable();

        if ($selectTable !== null) {
            return ['nullable', 'integer', Rule::exists($selectTable, 'id')];
        }

        return $this->rulesForType($setting->object_type);
    }

    /**
     * @return array<int, ValidationRule|string>
     */
    private function rulesForType(string $objectType): array
    {
        return match ($objectType) {
            'boolean', 'bool' => ['boolean'],
            'integer', 'int' => ['nullable', 'integer'],
            'number', 'numeric', 'float', 'decimal' => ['nullable', 'numeric'],
            default => ['nullable', 'string'],
        };
    }
}
