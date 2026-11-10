<?php

namespace App\Http\Requests\Exception;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExceptionStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['admin', 'operator'], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:OPEN,INVESTIGATING,RESOLVED'],
            'resolution_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
