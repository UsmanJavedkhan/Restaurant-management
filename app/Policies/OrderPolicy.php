<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OrderPolicy
{
    public function view(User $user, Order $order): Response
    {
        return $user->is_active && ($user->id === $order->user_id || $user->role === 'admin') ? Response::allow() : Response::denyAsNotFound();
    }
}
