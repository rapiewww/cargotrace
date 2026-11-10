<?php

namespace App\Models;

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
