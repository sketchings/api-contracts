<?php

namespace App\Controller;

use App\Dto\BuyMuseumTickets;
use App\Entity\Ticket;
use App\Enum\TicketType;
use App\Repository\SpecialEventRepository;
use App\Repository\TicketRepository;
use App\Service\TicketCodeGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

/**
 * Tag: Tickets.
 */
#[Route('/tickets')]
class TicketsController extends AbstractController
{
    #[Route('', name: 'buyMuseumTickets', methods: ['POST'])]
    public function buyMuseumTickets(
        #[MapRequestPayload(validationFailedStatusCode: 400)] BuyMuseumTickets $input,
        SpecialEventRepository $events,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $event = null;
        if (TicketType::Event === $input->ticketType) {
            $event = $events->find(Uuid::fromString($input->eventId))
                ?? throw $this->createNotFoundException(\sprintf('Special event "%s" not found.', $input->eventId));

            if (!$event->occursOn($input->ticketDate)) {
                throw new BadRequestHttpException(\sprintf('Special event "%s" does not take place on %s.', $event->getName(), $input->ticketDate));
            }
        }

        $ticket = new Ticket($input->ticketType, new \DateTimeImmutable($input->ticketDate), $event, $input->email);
        $entityManager->persist($ticket);
        $entityManager->flush();

        // `#/components/schemas/MuseumTicketsConfirmation`
        return $this->json([
            'message' => TicketType::Event === $ticket->getTicketType()
                ? 'Museum special event ticket purchased'
                : 'Museum general entry ticket purchased',
            ...$ticket->toArray(),
            'confirmationCode' => $ticket->getConfirmationCode(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/{ticketId}/qr', name: 'getTicketCode', methods: ['GET'])]
    public function getTicketCode(string $ticketId, TicketRepository $tickets, TicketCodeGenerator $generator): Response
    {
        if (!Uuid::isValid($ticketId)) {
            throw new BadRequestHttpException('ticketId must be a UUID.');
        }

        $ticket = $tickets->find(Uuid::fromString($ticketId))
            ?? throw $this->createNotFoundException(\sprintf('Ticket "%s" not found.', $ticketId));

        return new Response($generator->generatePng($ticket), headers: ['Content-Type' => 'image/png']);
    }
}
