<?php

namespace App\Repository;

use App\Entity\SpecialEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SpecialEvent>
 */
class SpecialEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SpecialEvent::class);
    }

    /**
     * Events with at least one date in the inclusive range. Dates are stored
     * as a JSON list, so the range is applied in PHP.
     *
     * @return list<SpecialEvent>
     */
    public function findOccurringBetween(?string $startDate, ?string $endDate, int $offset, int $limit): array
    {
        $events = $this->findBy([], ['name' => 'ASC']);

        if (null !== $startDate || null !== $endDate) {
            $events = array_filter(
                $events,
                static fn (SpecialEvent $event) => $event->occursBetween($startDate ?? '0000-00-00', $endDate ?? '9999-12-31'),
            );
        }

        return \array_slice(array_values($events), $offset, $limit);
    }
}
