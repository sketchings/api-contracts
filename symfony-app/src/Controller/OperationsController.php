<?php

namespace App\Controller;

use App\Dto\PaginationQuery;
use App\Service\MuseumHoursProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Tag: Operations.
 */
class OperationsController extends AbstractController
{
    #[Route('/museum-hours', name: 'getMuseumHours', methods: ['GET'])]
    public function getMuseumHours(
        MuseumHoursProvider $hoursProvider,
        #[MapQueryString(validationFailedStatusCode: 400)] PaginationQuery $query = new PaginationQuery(),
    ): JsonResponse {
        $startDate = new \DateTimeImmutable($query->startDate ?? 'today');
        $offset = $query->offset();

        return $this->json(null === $offset ? [] : $hoursProvider->getHours($startDate, $offset, $query->limit));
    }
}
