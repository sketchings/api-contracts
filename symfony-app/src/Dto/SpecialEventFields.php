<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * `#/components/schemas/SpecialEventFields`: every field optional, used for PATCH.
 */
class SpecialEventFields
{
    public function __construct(
        #[Assert\NotBlank(allowNull: true)]
        public ?string $name = null,
        #[Assert\NotBlank(allowNull: true)]
        public ?string $location = null,
        #[Assert\NotBlank(allowNull: true)]
        public ?string $eventDescription = null,
        /** @var list<string>|null */
        #[Assert\Type('list')]
        #[Assert\All([new Assert\NotBlank(), new Assert\Date()])]
        public ?array $dates = null,
        #[Assert\PositiveOrZero]
        public ?float $price = null,
    ) {
    }
}
