<?php

namespace App\Models;

use App\Support\Iso6346;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'shipment_id',
        'container_number',
        'sku',
        'description',
        'quantity',
        'unit_value',
        'currency_code',
        'weight_kg',
        'volume_cbm',
        'is_hazardous',
    ];

    /**
     * Formatted ISO 6346 container number (e.g. "MSKU 123456 5").
     */
    public function getFormattedContainerNumberAttribute(): ?string
    {
        $num = $this->container_number ?: $this->sku;

        return $num ? Iso6346::format($num) : null;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_value' => 'decimal:2',
            'weight_kg' => 'decimal:2',
            'volume_cbm' => 'decimal:2',
            'is_hazardous' => 'boolean',
        ];
    }

    /**
     * Shipment this item belongs to.
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }
}
