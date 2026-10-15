<?php

namespace App\Tests\Controller;

use App\Tests\ApiTestCase;

class EventsControllerTest extends ApiTestCase
{
    private const string MERMAID = 'dad4bce8-f5cb-4078-a211-995864315e39';

    public function testCreateSpecialEvent(): void
    {
        $event = $this->request('POST', '/special-events', [
            'name' => 'Mermaid Treasure Identification and Analysis',
            'location' => 'Under the seaaa 🦀 🎶 🌊.',
            'eventDescription' => 'Join us as we review and classify a rare collection of 20 thingamabobs.',
            'dates' => ['2023-09-05', '2023-09-08'],
            'price' => 0,
        ]);

        self::assertResponseStatusCodeSame(201);
        self::assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $event['eventId']);
        self::assertSame(['2023-09-05', '2023-09-08'], $event['dates']);
        self::assertSame($event, $this->request('GET', '/special-events/'.$event['eventId']));
    }

    public function testCreateSpecialEventRequiresAllFields(): void
    {
        $problem = $this->request('POST', '/special-events', ['name' => 'Incomplete', 'dates' => ['not-a-date']]);

        $this->assertProblem(400);
        self::assertEqualsCanonicalizing(
            ['location', 'eventDescription', 'dates[0]', 'price'],
            array_column($problem['violations'], 'propertyPath'),
        );
    }

    public function testListSpecialEvents(): void
    {
        self::assertCount(10, $this->request('GET', '/special-events?limit=30'));
        self::assertCount(3, $this->request('GET', '/special-events?limit=3&page=2'));
    }

    public function testListSpecialEventsInDateRange(): void
    {
        $events = $this->request('GET', '/special-events?startDate=2023-09-06&endDate=2023-09-10');

        self::assertSame(['Mermaid Treasure Identification and Analysis', 'Solar Telescope Demonstration'], array_column($events, 'name'));
    }

    public function testListSpecialEventsEndDateDefaultsToSevenDaysAfterStart(): void
    {
        $events = $this->request('GET', '/special-events?startDate=2023-10-29');

        self::assertSame(['Llama Street Art Through the Ages', 'Pirate Coding Workshop', 'The Great Parrot Debate'], array_column($events, 'name'));
    }

    public function testCreateSpecialEventIgnoresClientEventId(): void
    {
        $event = $this->request('POST', '/special-events', [
            'eventId' => '11111111-1111-4111-8111-111111111111',
            'name' => 'Sasquatch Ballet',
            'location' => 'Seattle... probably',
            'eventDescription' => 'Graceful.',
            'dates' => ['2023-12-15'],
            'price' => 40,
        ]);

        self::assertResponseStatusCodeSame(201);
        self::assertNotSame('11111111-1111-4111-8111-111111111111', $event['eventId']);
    }

    /**
     * Unknown keys used to reach PropertyAccess, which threw a 500 on keys like "[".
     */
    public function testUnknownFieldsAreRejected(): void
    {
        $problem = $this->request('POST', '/special-events', [
            '[' => 1,
            'name' => 'Sasquatch Ballet',
            'location' => 'Seattle... probably',
            'eventDescription' => 'Graceful.',
            'dates' => [],
            'price' => 40,
        ]);
        $this->assertProblem(400);
        self::assertSame('[', $problem['violations'][0]['propertyPath']);

        $this->request('PATCH', '/special-events/'.self::MERMAID, ['a[b' => 1]);
        $this->assertProblem(400);
    }

    public function testListSpecialEventsRejectsInvalidQuery(): void
    {
        foreach (['startDate=', 'endDate=', 'limit=0', 'page=0'] as $query) {
            $this->request('GET', '/special-events?'.$query);
            $this->assertProblem(400);
        }
    }

    public function testListSpecialEventsWithInvertedRangeIsEmpty(): void
    {
        self::assertSame([], $this->request('GET', '/special-events?startDate=2023-09-10&endDate=2023-09-06'));
        self::assertResponseIsSuccessful();
    }

    public function testListSpecialEventsBeyondTheLastPageIsEmpty(): void
    {
        self::assertSame([], $this->request('GET', '/special-events?page='.\PHP_INT_MAX.'&limit=30'));
        self::assertResponseIsSuccessful();
    }

    public function testRequestBodyMustBeAJsonObject(): void
    {
        foreach (['null', '[]', '"name"'] as $body) {
            $this->client->request('PATCH', '/special-events/'.self::MERMAID, server: ['CONTENT_TYPE' => 'application/json'], content: $body);
            $this->assertResponseMatchesOpenApiSchema($this->client->getRequest(), $this->client->getResponse());
            $this->assertProblem(400);
        }
    }

    public function testNullValuesAreRejected(): void
    {
        $problem = $this->request('PATCH', '/special-events/'.self::MERMAID, ['price' => null]);
        $this->assertProblem(400);
        self::assertSame('"price" must not be null.', $problem['detail']);

        $this->request('POST', '/special-events', [
            'eventId' => null,
            'name' => 'Sasquatch Ballet',
            'location' => 'Seattle... probably',
            'eventDescription' => 'Graceful.',
            'dates' => ['2023-12-15'],
            'price' => 40,
        ]);
        $this->assertProblem(400);
    }

    public function testDatesMustBeAList(): void
    {
        foreach ([new \stdClass(), ['first' => '2023-09-05']] as $dates) {
            $this->request('PATCH', '/special-events/'.self::MERMAID, ['dates' => $dates]);
            $this->assertProblem(400);
        }
    }

    public function testGetSpecialEvent(): void
    {
        $event = $this->request('GET', '/special-events/6744a0da-4121-49cd-8479-f8cc20526495');

        self::assertResponseIsSuccessful();
        self::assertSame([
            'eventId' => '6744a0da-4121-49cd-8479-f8cc20526495',
            'name' => 'Time Traveler Tea Party',
            'location' => 'Temporal Tearoom',
            'eventDescription' => 'Sip tea with important historical figures.',
            'dates' => ['2023-11-18', '2023-11-25', '2023-12-02'],
            'price' => 60,
        ], $event);
    }

    public function testGetSpecialEventErrors(): void
    {
        $this->request('GET', '/special-events/not-a-uuid');
        $this->assertProblem(400);

        $this->request('GET', '/special-events/00000000-0000-4000-8000-000000000000');
        $this->assertProblem(404);
    }

    public function testUpdateSpecialEventOnlyChangesGivenFields(): void
    {
        $event = $this->request('PATCH', '/special-events/'.self::MERMAID, ['location' => 'On the beach.', 'price' => 15]);

        self::assertResponseIsSuccessful();
        self::assertSame('On the beach.', $event['location']);
        self::assertSame(15, $event['price']);
        self::assertSame('Mermaid Treasure Identification and Analysis', $event['name']);
        self::assertSame(['2023-09-05', '2023-09-08'], $event['dates']);
    }

    public function testUpdateSpecialEventErrors(): void
    {
        $this->request('PATCH', '/special-events/'.self::MERMAID, ['price' => -1]);
        $this->assertProblem(400);

        $this->request('PATCH', '/special-events/00000000-0000-4000-8000-000000000000', ['price' => 15]);
        $this->assertProblem(404);
    }

    public function testDeleteSpecialEventErrors(): void
    {
        $this->request('DELETE', '/special-events/not-a-uuid');
        $this->assertProblem(400);

        $this->request('DELETE', '/special-events/00000000-0000-4000-8000-000000000000');
        $this->assertProblem(404);
    }

    public function testDeleteSpecialEvent(): void
    {
        $this->request('DELETE', '/special-events/'.self::MERMAID);
        self::assertResponseStatusCodeSame(204);

        $this->request('GET', '/special-events/'.self::MERMAID);
        $this->assertProblem(404);
    }
}
