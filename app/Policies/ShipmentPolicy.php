<?php

namespace App\Policies;

use App\Models\Shipment;
use App\Models\User;

class ShipmentPolicy
{
    /**
     * Tentukan apakah pengguna dapat melihat daftar atau detail pengiriman.
     */
    public function view(User $user, Shipment $shipment): bool
    {
        return $user->isAdmin() || $user->id === $shipment->user_id;
    }

    /**
     * Tentukan apakah pengguna dapat membuat pengiriman baru.
     */
    public function create(User $user): bool
    {
        return $user->isDistributor() || $user->isAdmin();
    }

    /**
     * Tentukan apakah pengguna dapat memperbarui status atau menambah event pengiriman.
     */
    public function update(User $user, Shipment $shipment): bool
    {
        return $user->isAdmin() || $user->id === $shipment->user_id;
    }

    /**
     * Tentukan apakah pengguna dapat menghapus pengiriman (khusus admin).
     */
    public function delete(User $user, Shipment $shipment): bool
    {
        return $user->isAdmin();
    }
}
