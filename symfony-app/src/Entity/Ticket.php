<?php

namespace App\Entity;

use App\Enum\TicketType;
use App\Repository\TicketRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * `#/components/schemas/Ticket`
 */
#[ORM\Entity(repositoryClass: TicketRepository::class)]
class Ticket
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\Column(length: 32, unique: true)]
    private string $confirmationCode;

    public function __construct(
        #[ORM\Column(enumType: TicketType::class)]
        private TicketType $ticketType,
        #[ORM\Column(type: Types::DATE_IMMUTABLE)]
        private \DateTimeImmutable $ticketDate,
        #[ORM\ManyToOne]
        #[ORM\JoinColumn(onDelete: 'SET NULL')]
        private ?SpecialEvent $event = null,
        #[ORM\Column(length: 255, nullable: true)]
        private ?string $email = null,
    ) {
        $this->id = Uuid::v4();
        // e.g. ticket-event-a98c8f-7eb12
        $hex = bin2hex(random_bytes(6));
        $this->confirmationCode = \sprintf('ticket-%s-%s-%s', $ticketType->value, substr($hex, 0, 6), substr($hex, 6, 5));
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getTicketType(): TicketType
    {
        return $this->ticketType;
    }

    public function getTicketDate(): \DateTimeImmutable
    {
        return $this->ticketDate;
    }

    public function getEvent(): ?SpecialEvent
    {
        return $this->event;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getConfirmationCode(): string
    {
        return $this->confirmationCode;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'ticketId' => $this->id->toRfc4122(),
            'ticketDate' => $this->ticketDate->format('Y-m-d'),
            'ticketType' => $this->ticketType->value,
            'eventId' => $this->event?->getId()->toRfc4122(),
        ], static fn ($value) => null !== $value);
    }
}
