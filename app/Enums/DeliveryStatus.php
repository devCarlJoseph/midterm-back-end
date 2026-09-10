<?php

namespace App\Enums;

enum DeliveryStatus: string
{
    case Assigned = 'assigned';
    case PickedUp = 'picked_up';
    case Delivered = 'delivered';
}
