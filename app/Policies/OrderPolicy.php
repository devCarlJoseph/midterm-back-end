<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Order;
use App\Models\User;
use App\Enums\OrderStatus;

class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        return $user->id === $order->user_id
            || $this->managesStore($user, $order);
    }

    public function manageFulfillment(User $user, Order $order): bool
    {
        return $this->managesStore($user, $order);
    }

    private function managesStore(User $user, Order $order): bool
    {
        if ($user->role === UserRole::Admin) {
            return true;
        }

        return $user->role === UserRole::Merchant
            && $order->store
            ->users()
            ->whereKey($user)
            ->exists();
    }

    public function viewMerchantOrders(User $user): bool
    {
        return $user->role === UserRole::Merchant
            || $user->role === UserRole::Admin;
    }

    public function cancel(User $user, Order $order): bool
    {
        return $user->id === $order->user_id
            && $order->status === OrderStatus::Pending;
    }
}
