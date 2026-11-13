<?php

namespace App\Http\Requests\Shipment;

use App\Models\Shipment;
use Illuminate\Foundation\Http\FormRequest;

class ImportShipmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('import', Shipment::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'csv_file' => [
                'required',
                'file',
                'mimes:csv,txt',
                'max:2048', // 2 MB
            ],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'csv_file.required' => 'Please select a CSV file to upload.',
            'csv_file.file' => 'The uploaded item must be a valid file.',
            'csv_file.mimes' => 'The file must be a CSV file (.csv).',
            'csv_file.max' => 'The uploaded file exceeds the maximum allowed size of 2 MB.',
        ];
    }
}

