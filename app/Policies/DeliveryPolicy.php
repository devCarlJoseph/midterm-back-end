<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Delivery;
use App\Models\User;

class DeliveryPolicy
{
    public function viewAvailable(User $user): bool
    {
        return $user->role === UserRole::Driver
            || $user->role === UserRole::Admin;
    }

    public function view(User $user, Delivery $delivery): bool
    {
        return $user->role === UserRole::Admin
            || $user->id === $delivery->driver_id;
    }

    public function update(User $user, Delivery $delivery): bool
    {
        return $this->view($user, $delivery);
    }
}
