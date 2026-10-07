<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClassScheduleRequest extends FormRequest
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
        if ($this->isMethod('POST')) {
            return [
                'modality_id' => ['required', 'integer', 'exists:modalities,id'],
                'week_day' => ['required', 'integer', 'min:1', 'max:6'],
                'start_time' => ['required', 'string', 'date_format:H:i'],
                'end_time' => ['required', 'string', 'date_format:H:i', 'after:start_time'],
            ];
        }

        return [
            'modality_id' => ['nullable', 'integer', 'exists:modalities,id'],
            'week_day' => ['nullable', 'integer', 'min:1', 'max:6'],
            'start_time' => ['nullable', 'string', 'date_format:H:i'],
            'end_time' => ['nullable', 'string', 'date_format:H:i'],
        ];
    }
}
