<?php

namespace App\Entity;

use App\Repository\SpecialEventRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * `#/components/schemas/SpecialEvent`
 */
#[ORM\Entity(repositoryClass: SpecialEventRepository::class)]
class SpecialEvent
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    /**
     * @param list<string> $dates Dates in `Y-m-d` format
     */
    public function __construct(
        #[ORM\Column(length: 255)]
        private string $name,
        #[ORM\Column(length: 255)]
        private string $location,
        #[ORM\Column(type: Types::TEXT)]
        private string $eventDescription,
        #[ORM\Column(type: Types::JSON)]
        private array $dates,
        #[ORM\Column]
        private float $price,
        ?Uuid $id = null,
    ) {
        $this->id = $id ?? Uuid::v4();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function setLocation(string $location): void
    {
        $this->location = $location;
    }

    public function getEventDescription(): string
    {
        return $this->eventDescription;
    }

    public function setEventDescription(string $eventDescription): void
    {
        $this->eventDescription = $eventDescription;
    }

    /**
     * @return list<string>
     */
    public function getDates(): array
    {
        return $this->dates;
    }

    /**
     * @param list<string> $dates
     */
    public function setDates(array $dates): void
    {
        $this->dates = array_values($dates);
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function setPrice(float $price): void
    {
        $this->price = $price;
    }

    public function occursOn(string $date): bool
    {
        return \in_array($date, $this->dates, true);
    }

    public function occursBetween(string $startDate, string $endDate): bool
    {
        foreach ($this->dates as $date) {
            if ($date >= $startDate && $date <= $endDate) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'eventId' => $this->id->toRfc4122(),
            'name' => $this->name,
            'location' => $this->location,
            'eventDescription' => $this->eventDescription,
            'dates' => $this->dates,
            'price' => $this->price,
        ];
    }
}
