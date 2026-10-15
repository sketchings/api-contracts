<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Query parameters for `GET /museum-hours`.
 */
class PaginationQuery
{
    public function __construct(
        /** `#/components/parameters/StartDate` */
        #[Assert\Date]
        public ?string $startDate = null,
        /** `#/components/parameters/PaginationPage` */
        #[Assert\Positive]
        public int $page = 1,
        /** `#/components/parameters/PaginationLimit` */
        #[Assert\Range(min: 1, max: 30)]
        public int $limit = 10,
    ) {
    }
}
