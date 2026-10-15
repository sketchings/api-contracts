<?php

namespace App\Tests\Controller;

use App\Tests\ApiTestCase;

class TicketsControllerTest extends ApiTestCase
{
    private const string MERMAID = 'dad4bce8-f5cb-4078-a211-995864315e39';

    public function testBuyGeneralTicket(): void
    {
        $confirmation = $this->request('POST', '/tickets', [
            'ticketType' => 'general',
            'ticketDate' => '2023-09-07',
            'email' => 'todd@example.com',
        ]);

        self::assertResponseStatusCodeSame(201);
        self::assertSame('Museum general entry ticket purchased', $confirmation['message']);
        self::assertSame('general', $confirmation['ticketType']);
        self::assertSame('2023-09-07', $confirmation['ticketDate']);
        self::assertArrayNotHasKey('eventId', $confirmation);
        self::assertMatchesRegularExpression('/^ticket-general-[0-9a-f]{6}-[0-9a-f]{5}$/', $confirmation['confirmationCode']);
    }

    public function testBuyEventTicket(): void
    {
        $confirmation = $this->request('POST', '/tickets', [
            'ticketType' => 'event',
            'eventId' => self::MERMAID,
            'ticketDate' => '2023-09-05',
            'email' => 'todd@example.com',
        ]);

        self::assertResponseStatusCodeSame(201);
        self::assertSame('Museum special event ticket purchased', $confirmation['message']);
        self::assertSame(self::MERMAID, $confirmation['eventId']);
    }

    public function testBuyEventTicketValidation(): void
    {
        $this->request('POST', '/tickets', ['ticketType' => 'event', 'ticketDate' => '2023-09-05']);
        $this->assertProblem(400);

        $this->request('POST', '/tickets', ['ticketType' => 'event', 'eventId' => self::MERMAID, 'ticketDate' => '2023-09-06']);
        $this->assertProblem(400);

        $this->request('POST', '/tickets', ['ticketType' => 'event', 'eventId' => '00000000-0000-4000-8000-000000000000', 'ticketDate' => '2023-09-05']);
        $this->assertProblem(404);

        $this->request('POST', '/tickets', ['ticketType' => 'vip', 'ticketDate' => '2023-09-05']);
        $this->assertProblem(400);

        // Empty strings used to slip past the Uuid/Date/Email constraints (eventId "" was a 500).
        foreach ([['eventId' => ''], ['ticketDate' => ''], ['email' => '']] as $blank) {
            $this->request('POST', '/tickets', [...['ticketType' => 'event', 'eventId' => self::MERMAID, 'ticketDate' => '2023-09-05'], ...$blank]);
            $this->assertProblem(400);
        }

        $this->request('POST', '/tickets', ['ticketType' => 'general', 'eventId' => self::MERMAID, 'ticketDate' => '2023-09-05']);
        $this->assertProblem(400);

        $this->request('POST', '/tickets', ['ticketType' => 'general', 'ticketId' => self::MERMAID, 'ticketDate' => '2023-09-05']);
        $this->assertProblem(400);
    }

    public function testGetTicketCode(): void
    {
        $confirmation = $this->request('POST', '/tickets', ['ticketType' => 'general', 'ticketDate' => '2023-09-07']);

        $this->client->request('GET', '/tickets/'.$confirmation['ticketId'].'/qr');
        $this->assertClientMatchesOpenApiSchema($this->client);

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'image/png');
        self::assertStringStartsWith("\x89PNG", $this->client->getResponse()->getContent());
    }

    public function testGetTicketCodeErrors(): void
    {
        $this->client->request('GET', '/tickets/not-a-uuid/qr');
        $this->assertClientMatchesOpenApiSchema($this->client);
        $this->assertProblem(400);


        $this->client->request('GET', '/tickets/00000000-0000-4000-8000-000000000000/qr');
        $this->assertClientMatchesOpenApiSchema($this->client);
        $this->assertProblem(404);
    }
}
