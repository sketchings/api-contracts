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
        #[Assert\NotBlank(allowNull: true)]
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

    /**
     * Number of items to skip, or null when it is too large to represent.
     */
    public function offset(): ?int
    {
        return $this->page - 1 > intdiv(\PHP_INT_MAX, $this->limit) ? null : ($this->page - 1) * $this->limit;
    }
}
