<?php

namespace App\Tests\Controller;

use App\Tests\ApiTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Responses every operation shares: 401 without credentials, 415 without a JSON body.
 */
class RequestHandlingTest extends ApiTestCase
{
    private const string MERMAID = 'dad4bce8-f5cb-4078-a211-995864315e39';
    private const string TICKET = 'a54a57ca-36f8-421b-a6b4-2e8f26858a4c';

    /**
     * Each request is otherwise valid, so missing credentials are the only problem.
     *
     * @return iterable<string, array{string, string, array<string, mixed>|null}>
     */
    public static function operations(): iterable
    {
        $event = ['name' => 'Eat a Bunch of Corn', 'location' => 'Cafeteria', 'eventDescription' => 'Corn.', 'dates' => ['2023-11-10'], 'price' => 5];

        yield 'getMuseumHours' => ['GET', '/museum-hours', null];
        yield 'createSpecialEvent' => ['POST', '/special-events', $event];
        yield 'listSpecialEvents' => ['GET', '/special-events', null];
        yield 'getSpecialEvent' => ['GET', '/special-events/'.self::MERMAID, null];
        yield 'updateSpecialEvent' => ['PATCH', '/special-events/'.self::MERMAID, ['price' => 15]];
        yield 'deleteSpecialEvent' => ['DELETE', '/special-events/'.self::MERMAID, null];
        yield 'buyMuseumTickets' => ['POST', '/tickets', ['ticketType' => 'general', 'ticketDate' => '2023-09-07']];
        yield 'getTicketCode' => ['GET', '/tickets/'.self::TICKET.'/qr', null];
    }

    /**
     * @param array<string, mixed>|null $body
     */
    #[DataProvider('operations')]
    public function testRequiresBasicAuth(string $method, string $uri, ?array $body): void
    {
        $this->client->setServerParameters([]);
        $this->request($method, $uri, $body);

        $this->assertProblem(401);
        self::assertResponseHeaderSame('WWW-Authenticate', 'Basic realm="Museum API"');
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function operationsWithBody(): iterable
    {
        yield 'createSpecialEvent' => ['POST', '/special-events'];
        yield 'updateSpecialEvent' => ['PATCH', '/special-events/'.self::MERMAID];
        yield 'buyMuseumTickets' => ['POST', '/tickets'];
    }

    #[DataProvider('operationsWithBody')]
    public function testRequiresJsonBody(string $method, string $uri): void
    {
        $this->client->request($method, $uri, server: ['CONTENT_TYPE' => 'text/plain'], content: 'name=Corn');
        // The request deliberately breaks the contract, so only the response is checked.
        $this->assertResponseMatchesOpenApiSchema($this->client->getRequest(), $this->client->getResponse());

        $this->assertProblem(415);
    }
}
