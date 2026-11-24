<?php

namespace App\Http\Requests\Exception;

use App\Services\ExceptionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        $allCodes = [
            'CUSTOMS_HOLD',
            'WEATHER_DELAY',
            'PORT_CONGESTION',
            'DAMAGED',
            'MISROUTED',
            'DOCUMENTATION_ISSUE',
            'FLIGHT_OFFLOAD',
            'BORDER_QUEUE',
            'VEHICLE_BREAKDOWN',
        ];

        return [
            'exception_code' => ['required', 'string', Rule::in($allCodes)],
            'severity' => ['required', 'string', 'in:LOW,MEDIUM,HIGH'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'related_news_url' => ['nullable', 'url', 'max:500'],
            'detected_at' => ['nullable', 'date'],
        ];
    }

    /**
     * Perform mode-dependent exception code validation.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $shipment = $this->route('shipment');
            $code = strtoupper((string) $this->input('exception_code'));

            if (! $shipment || ! $code) {
                return;
            }

            $mode = strtoupper($shipment->transport_mode ?? 'SEA');
            $allowed = ExceptionService::MODE_ALLOWED_EXCEPTIONS[$mode] ?? ExceptionService::MODE_ALLOWED_EXCEPTIONS['SEA'];

            if (! in_array($code, $allowed, true)) {
                $validator->errors()->add(
                    'exception_code',
                    "Exception code [{$code}] is not allowed for transport mode [{$mode}]."
                );
            }
        });
    }
}
