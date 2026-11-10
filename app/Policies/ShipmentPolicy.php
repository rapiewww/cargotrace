<?php

namespace App\Policies;

use App\Models\Shipment;
use App\Models\User;

class ShipmentPolicy
{
    /**
     * Perform pre-authorization checks. Admin has universal full access.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if (! $user->is_active) {
            return false;
        }

        return null;
    }

    /**
     * Determine whether the user can view any shipments.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'operator', 'customer'], true);
    }

    /**
     * Determine whether the user can view the shipment.
     * Customers may only view shipments belonging to their user account.
     */
    public function view(User $user, Shipment $shipment): bool
    {
        if ($user->role === 'operator') {
            return true;
        }

        if ($user->role === 'customer') {
            return (int) $shipment->customer_id === (int) $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create shipments.
     * Restricted to admin and operator.
     */
    public function create(User $user): bool
    {
        return $user->role === 'operator';
    }

    /**
     * Determine whether the user can update the shipment.
     * Restricted to admin and operator.
     */
    public function update(User $user, Shipment $shipment): bool
    {
        return $user->role === 'operator';
    }

    /**
     * Determine whether the user can delete the shipment.
     * Strictly restricted to admin.
     */
    public function delete(User $user, Shipment $shipment): bool
    {
        return false; // Handled by before() for admin
    }

    /**
     * Determine whether the user can append tracking events.
     */
    public function addTrackingEvent(User $user, Shipment $shipment): bool
    {
        return $user->role === 'operator';
    }

    /**
     * Determine whether the user can log exceptions.
     */
    public function reportException(User $user, Shipment $shipment): bool
    {
        return $user->role === 'operator';
    }
}

