<?php

namespace App\Service;

use App\Entity\Ticket;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

/**
 * Renders `#/components/schemas/TicketCodeImage`.
 */
class TicketCodeGenerator
{
    public function generatePng(Ticket $ticket): string
    {
        $qrCode = new QrCode(
            data: $ticket->getConfirmationCode(),
            size: 300,
            margin: 10,
        );

        return new PngWriter()->write($qrCode)->getString();
    }
}
