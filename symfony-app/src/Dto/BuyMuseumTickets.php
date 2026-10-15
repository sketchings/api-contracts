<?php

namespace App\Dto;

use App\Enum\TicketType;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * `#/components/schemas/BuyMuseumTickets`
 */
class BuyMuseumTickets
{
    public function __construct(
        #[Assert\NotNull]
        public ?TicketType $ticketType = null,
        #[Assert\NotBlank]
        #[Assert\Date]
        public ?string $ticketDate = null,
        #[Assert\NotBlank(allowNull: true)]
        #[Assert\Uuid]
        public ?string $eventId = null,
        #[Assert\NotBlank(allowNull: true)]
        #[Assert\Email]
        public ?string $email = null,
    ) {
    }

    #[Assert\Callback]
    public function validateEventId(ExecutionContextInterface $context): void
    {
        if (TicketType::Event === $this->ticketType && null === $this->eventId) {
            $context->buildViolation('eventId is required when purchasing event tickets.')
                ->atPath('eventId')
                ->addViolation();
        }

        if (TicketType::General === $this->ticketType && null !== $this->eventId) {
            $context->buildViolation('eventId must not be set for general entry tickets.')
                ->atPath('eventId')
                ->addViolation();
        }
    }
}
