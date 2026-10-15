<?php

namespace App\Controller;

use App\Dto\SpecialEventFields;
use App\Dto\SpecialEventInput;
use App\Dto\SpecialEventQuery;
use App\Entity\SpecialEvent;
use App\Repository\SpecialEventRepository;
use App\Service\NewEventPublisher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

/**
 * Tag: Events.
 */
#[Route('/special-events')]
class EventsController extends AbstractController
{
    public function __construct(
        private readonly SpecialEventRepository $events,
        private readonly EntityManagerInterface $entityManager,
        private readonly NewEventPublisher $publisher,
    ) {
    }

    #[Route('', name: 'createSpecialEvent', methods: ['POST'])]
    public function createSpecialEvent(
        #[MapRequestPayload(validationFailedStatusCode: 400)] SpecialEventInput $input,
    ): JsonResponse {
        $event = new SpecialEvent($input->name, $input->location, $input->eventDescription, array_values($input->dates), $input->price);

        $this->entityManager->persist($event);
        $this->entityManager->flush();
        $this->publisher->publish($event);

        return $this->json($event->toArray(), Response::HTTP_CREATED);
    }

    #[Route('', name: 'listSpecialEvents', methods: ['GET'])]
    public function listSpecialEvents(
        #[MapQueryString(validationFailedStatusCode: 400)] SpecialEventQuery $query = new SpecialEventQuery(),
    ): JsonResponse {
        $startDate = $query->startDate;
        $endDate = $query->endDate;
        // endDate defaults to 7 days after startDate; startDate defaults to today.
        if (null !== $startDate && null === $endDate) {
            $endDate = new \DateTimeImmutable($startDate)->modify('+7 days')->format('Y-m-d');
        } elseif (null === $startDate && null !== $endDate) {
            $startDate = new \DateTimeImmutable('today')->format('Y-m-d');
        }

        $offset = $query->offset();
        $events = null === $offset ? [] : $this->events->findOccurringBetween($startDate, $endDate, $offset, $query->limit);

        return $this->json(array_map(static fn (SpecialEvent $event) => $event->toArray(), $events));
    }

    #[Route('/{eventId}', name: 'getSpecialEvent', methods: ['GET'])]
    public function getSpecialEvent(string $eventId): JsonResponse
    {
        return $this->json($this->findEvent($eventId)->toArray());
    }

    #[Route('/{eventId}', name: 'updateSpecialEvent', methods: ['PATCH'])]
    public function updateSpecialEvent(
        string $eventId,
        #[MapRequestPayload(validationFailedStatusCode: 400)] SpecialEventFields $fields,
    ): JsonResponse {
        $event = $this->findEvent($eventId);

        if (null !== $fields->name) {
            $event->setName($fields->name);
        }
        if (null !== $fields->location) {
            $event->setLocation($fields->location);
        }
        if (null !== $fields->eventDescription) {
            $event->setEventDescription($fields->eventDescription);
        }
        if (null !== $fields->dates) {
            $event->setDates($fields->dates);
        }
        if (null !== $fields->price) {
            $event->setPrice($fields->price);
        }

        $this->entityManager->flush();
        $this->publisher->publish($event);

        return $this->json($event->toArray());
    }

    #[Route('/{eventId}', name: 'deleteSpecialEvent', methods: ['DELETE'])]
    public function deleteSpecialEvent(string $eventId): Response
    {
        $this->entityManager->remove($this->findEvent($eventId));
        $this->entityManager->flush();

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    private function findEvent(string $eventId): SpecialEvent
    {
        if (!Uuid::isValid($eventId)) {
            throw new BadRequestHttpException('eventId must be a UUID.');
        }

        return $this->events->find(Uuid::fromString($eventId))
            ?? throw $this->createNotFoundException(\sprintf('Special event "%s" not found.', $eventId));
    }
}
