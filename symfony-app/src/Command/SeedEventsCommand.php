<?php

namespace App\Command;

use App\Entity\SpecialEvent;
use App\Repository\SpecialEventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\Uuid;

/**
 * Loads the special events from `#/components/examples/ListSpecialEventsResponseExample`.
 */
#[AsCommand(name: 'app:seed-events', description: 'Load the example special events from the OpenAPI description')]
class SeedEventsCommand
{
    private const array EVENTS = [
        ['f3e0e76e-e4a8-466e-ab9c-ae36c15b8e97', 'Sasquatch Ballet', 'Seattle... probably', "They're big, they're hairy, but they're also graceful. Come learn how the biggest feet can have the lightest touch.", ['2023-12-15', '2023-12-22'], 40],
        ['2f14374a-9c65-4ee5-94b7-fba66d893483', 'Solar Telescope Demonstration', 'Far from the sun.', 'Look at the sun without going blind!', ['2023-09-07', '2023-09-14'], 50],
        ['6aaa61ba-b2aa-4868-b803-603dbbf7bfdb', 'Cook like a Caveman', 'Fire Pit on East side', 'Learn to cook on an open flame.', ['2023-11-10', '2023-11-17', '2023-11-24'], 5],
        ['602b75e1-5696-4ab8-8c7a-f9e13580f910', 'Underwater Basket Weaving', 'Rec Center Pool next door.', 'Learn to weave baskets underwater.', ['2023-09-12', '2023-09-15'], 15],
        ['dad4bce8-f5cb-4078-a211-995864315e39', 'Mermaid Treasure Identification and Analysis', 'Room Sea-12', 'Join us as we review and classify a rare collection of 20 thingamabobs, gadgets, gizmos, whoosits, and whatsits — kindly donated by Ariel.', ['2023-09-05', '2023-09-08'], 30],
        ['6744a0da-4121-49cd-8479-f8cc20526495', 'Time Traveler Tea Party', 'Temporal Tearoom', 'Sip tea with important historical figures.', ['2023-11-18', '2023-11-25', '2023-12-02'], 60],
        ['3be6453c-03eb-4357-ae5a-984a0e574a54', 'Pirate Coding Workshop', 'Computer Room', 'Captain Blackbeard shares his love of the C...language. And possibly Arrrrr (R lang).', ['2023-10-29', '2023-10-30', '2023-10-31'], 45],
        ['9d90d29a-2af5-4206-97d9-9ea9ceadcb78', 'Llama Street Art Through the Ages', 'Auditorium', "Llama street art?! Alpaca my bags -- let's go!", ['2023-10-29', '2023-10-30', '2023-10-31'], 45],
        ['a3c7b2c4-b5fb-4ef7-9322-00a919864957', 'The Great Parrot Debate', 'Outdoor Amphitheatre', 'See leading parrot minds discuss important geopolitical issues.', ['2023-11-03', '2023-11-10'], 35],
        ['b92d46b7-4c5d-422b-87a5-287767e26f29', 'Eat a Bunch of Corn', 'Cafeteria', 'We accidentally bought too much corn. Please come eat it.', ['2023-11-10', '2023-11-17', '2023-11-24'], 5],
    ];

    public function __construct(
        private readonly SpecialEventRepository $events,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $created = 0;
        foreach (self::EVENTS as [$id, $name, $location, $description, $dates, $price]) {
            $uuid = Uuid::fromString($id);
            if (null !== $this->events->find($uuid)) {
                continue;
            }

            $this->entityManager->persist(new SpecialEvent($name, $location, $description, $dates, $price, $uuid));
            ++$created;
        }
        $this->entityManager->flush();

        $io->success(\sprintf('Created %d special event(s).', $created));

        return 0;
    }
}
