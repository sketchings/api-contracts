<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Query parameters for `GET /special-events`. An `endDate` before `startDate`
 * is not an error: the range simply contains no events.
 */
class SpecialEventQuery extends PaginationQuery
{
    public function __construct(
        ?string $startDate = null,
        /** `#/components/parameters/EndDate` */
        #[Assert\NotBlank(allowNull: true)]
        #[Assert\Date]
        public ?string $endDate = null,
        int $page = 1,
        int $limit = 10,
    ) {
        parent::__construct($startDate, $page, $limit);
    }
}
