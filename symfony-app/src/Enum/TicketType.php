<?php

namespace App\Enum;

/**
 * `#/components/schemas/TicketType`
 */
enum TicketType: string
{
    case Event = 'event';
    case General = 'general';
}
