<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClientImageRightsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'image_producer_name' => ['nullable', 'string', 'max:255'],
            'image_usage_purpose' => ['nullable', 'string', 'max:1000'],
            'image_description' => ['nullable', 'string', 'max:1000'],
            'image_material_type' => ['nullable', 'string', 'max:255'],
            'site_owner_name' => ['nullable', 'string', 'max:255'],
            'site_name' => ['nullable', 'string', 'max:255'],
            'site_domain' => ['nullable', 'string', 'max:255'],
            'forum_city' => ['nullable', 'string', 'max:120'],
            'legal_representative_relationship' => ['nullable', 'string', 'max:120'],
        ];
    }
}
