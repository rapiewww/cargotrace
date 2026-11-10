<?php

namespace App\Http\Requests\Exception;

use Illuminate\Foundation\Http\FormRequest;

class StoreShipmentExceptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('reportException', $this->route('shipment')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'exception_code' => ['required', 'string', 'in:CUSTOMS_HOLD,WEATHER_DELAY,PORT_CONGESTION,DAMAGED,MISROUTED,DOCUMENTATION_ISSUE'],
            'severity' => ['required', 'string', 'in:LOW,MEDIUM,HIGH'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'related_news_url' => ['nullable', 'url', 'max:500'],
            'detected_at' => ['nullable', 'date'],
        ];
    }
}

