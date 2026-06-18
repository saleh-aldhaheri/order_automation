<?php

namespace App\Enums;

enum OrderStatusEnum: string
{
    case PROCESSED = 'processed';
    case UNPROCESSED = 'unprocessed';
    case CANCELLED = 'cancelled';
    case RETURNING = 'returning';
}
