<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * `#/components/schemas/SpecialEvent` as a request body: all fields except `eventId` are required.
 */
class SpecialEventInput
{
    public function __construct(
        #[Assert\NotBlank]
        public ?string $name = null,
        #[Assert\NotBlank]
        public ?string $location = null,
        #[Assert\NotBlank]
        public ?string $eventDescription = null,
        /** @var list<string>|null */
        #[Assert\NotNull]
        #[Assert\All([new Assert\NotBlank(), new Assert\Date()])]
        public ?array $dates = null,
        #[Assert\NotNull]
        #[Assert\PositiveOrZero]
        public ?float $price = null,
    ) {
    }
}
