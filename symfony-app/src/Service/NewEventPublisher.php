<?php

namespace App\Service;

use App\Entity\SpecialEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Sends the `publishNewEvent` webhook for new or updated special events.
 *
 * Events are queued during the request and delivered on `kernel.terminate`,
 * after the response has been sent to the API client.
 */
class NewEventPublisher
{
    /** @var array<string, array<string, mixed>> */
    private array $queue = [];

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        #[Autowire(env: 'PUBLISH_NEW_EVENT_WEBHOOK_URL')]
        private readonly string $webhookUrl,
    ) {
    }

    public function publish(SpecialEvent $event): void
    {
        if ('' === $this->webhookUrl) {
            return;
        }

        $this->queue[$event->getId()->toRfc4122()] = $event->toArray();
    }

    #[AsEventListener(KernelEvents::TERMINATE)]
    public function flush(): void
    {
        $payloads = $this->queue;
        $this->queue = [];

        foreach ($payloads as $eventId => $payload) {
            try {
                $status = $this->httpClient->request('POST', $this->webhookUrl, ['json' => $payload])->getStatusCode();
                if (202 !== $status) {
                    $this->logger->warning('publishNewEvent webhook for {eventId} returned HTTP {status}.', ['eventId' => $eventId, 'status' => $status]);
                }
            } catch (ExceptionInterface $e) {
                $this->logger->error('publishNewEvent webhook for {eventId} failed: {message}', ['eventId' => $eventId, 'message' => $e->getMessage()]);
            }
        }
    }
}
