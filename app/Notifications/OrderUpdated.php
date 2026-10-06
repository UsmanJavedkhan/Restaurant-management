<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Notifications\Notification;

class OrderUpdated extends Notification
{
    private array $data;

    public function __construct(Order $order, bool $admin)
    {
        $this->data = ['order_id' => $order->id, 'order_number' => $order->order_number, 'status' => $order->order_status, 'message' => ($admin ? 'Order ' : 'Your order ').$order->order_number.' is '.str_replace('_', ' ', $order->order_status).'.', 'url' => $admin ? '/admin/orders/'.$order->id : '/account/orders/'.$order->id];
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return $this->data;
    }
}
