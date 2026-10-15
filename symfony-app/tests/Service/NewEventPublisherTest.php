<?php

namespace App\Tests\Service;

use App\Entity\SpecialEvent;
use App\Service\NewEventPublisher;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;

class NewEventPublisherTest extends TestCase
{
    public function testPostsEventToWebhookOnTerminate(): void
    {
        $response = new JsonMockResponse(info: ['http_code' => 202]);
        $client = new MockHttpClient($response);
        $publisher = new NewEventPublisher($client, new NullLogger(), 'https://subscriber.example/hooks');
        $event = new SpecialEvent('Time Traveler Tea Party', 'Temporal Tearoom', 'Sip tea.', ['2023-11-18'], 60);

        $publisher->publish($event);
        $publisher->publish($event);
        self::assertSame(0, $client->getRequestsCount(), 'Nothing is sent before kernel.terminate.');

        $publisher->flush();

        self::assertSame(1, $client->getRequestsCount(), 'Repeated publishes of one event are sent once.');
        self::assertSame('POST', $response->getRequestMethod());
        self::assertSame('https://subscriber.example/hooks', $response->getRequestUrl());
        self::assertSame($event->toArray(), json_decode($response->getRequestOptions()['body'], true));
    }

    public function testDoesNothingWithoutWebhookUrl(): void
    {
        $client = new MockHttpClient();
        $publisher = new NewEventPublisher($client, new NullLogger(), '');

        $publisher->publish(new SpecialEvent('Name', 'Location', 'Description', ['2023-11-18'], 60));
        $publisher->flush();

        self::assertSame(0, $client->getRequestsCount());
    }
}
