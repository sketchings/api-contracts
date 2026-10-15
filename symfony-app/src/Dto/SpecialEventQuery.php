<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Query parameters for `GET /special-events`.
 */
class SpecialEventQuery extends PaginationQuery
{
    public function __construct(
        ?string $startDate = null,
        /** `#/components/parameters/EndDate` */
        #[Assert\Date]
        public ?string $endDate = null,
        int $page = 1,
        int $limit = 10,
    ) {
        parent::__construct($startDate, $page, $limit);
    }

    #[Assert\Callback]
    public function validateRange(ExecutionContextInterface $context): void
    {
        if (null !== $this->startDate && null !== $this->endDate && $this->endDate < $this->startDate) {
            $context->buildViolation('endDate must not be before startDate.')
                ->atPath('endDate')
                ->addViolation();
        }
    }
}
