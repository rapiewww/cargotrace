<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'unlocode',
        'iata_code',
        'name',
        'city',
        'country',
        'country_code',
        'currency_code',
        'type',
        'latitude',
        'longitude',
        'timezone',
        'weather_snapshot',
        'news_snapshot',
    ];

    /**
     * Computed attributes included in JSON serialization.
     *
     * @var list<string>
     */
    protected $appends = [
        'display_code',
        'code_type_label',
        'short_name',
    ];

    /**
     * Primary code for display: IATA for airports, UN/LOCODE for seaports, internal code for depots/borders.
     */
    public function getDisplayCodeAttribute(): string
    {
        if (strtoupper((string) $this->type) === 'AIRPORT' && ! empty($this->iata_code)) {
            return strtoupper((string) $this->iata_code);
        }

        return (string) $this->unlocode;
    }

    /**
     * Human-readable label describing the code standard.
     */
    public function getCodeTypeLabelAttribute(): string
    {
        return match (strtoupper((string) $this->type)) {
            'AIRPORT' => 'IATA',
            'PORT', 'SEAPORT' => 'UN/LOCODE',
            default => 'Internal code',
        };
    }

    /**
     * Unambiguous place name for route displays (distinguishes ports, airports, and depots in the same city).
     */
    public function getShortNameAttribute(): string
    {
        $code = strtoupper($this->display_code);

        $named = [
            'IDBLW' => 'Belawan port',
            'IDJKT' => 'Tanjung Priok port',
            'INNSA' => 'Nhava Sheva port',
            'AEJEA' => 'Jebel Ali port',
            'FRMRS' => 'Marseille-Fos port',
            'MYPKG' => 'Port Klang',
            'USNYC' => 'New York/NJ port',
            'DEP-NSC' => 'Nhava Sheva CFS',
        ];

        if (isset($named[$code])) {
            return $named[$code];
        }

        return match (strtoupper((string) $this->type)) {
            'PORT', 'SEAPORT' => "{$this->city} port",
            'AIRPORT' => "{$this->city} airport",
            'DEPOT' => "{$this->city} depot",
            'WAREHOUSE' => "{$this->city} warehouse",
            'BORDER' => "{$this->city} border",
            default => (string) $this->name,
        };
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'weather_snapshot' => 'array',
            'news_snapshot' => 'array',
        ];
    }

    /**
     * Shipments originating from this location.
     */
    public function originShipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'origin_location_id');
    }

    /**
     * Shipments arriving at this location.
     */
    public function destinationShipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'destination_location_id');
    }

    /**
     * Shipments currently located at this location.
     */
    public function currentShipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'current_location_id');
    }

    /**
     * Tracking events recorded at this location.
     */
    public function trackingEvents(): HasMany
    {
        return $this->hasMany(TrackingEvent::class, 'location_id');
    }
}
