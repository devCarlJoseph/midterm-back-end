<?php

namespace App\Enums;

enum DeliveryOptionName: string
{
    case Saver = 'saver';
    case Standard = 'standard';
    case Express = 'express';
}