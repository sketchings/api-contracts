<?php

namespace App\Tests\EventListener;

use App\EventListener\ProblemExceptionListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ProblemExceptionListenerTest extends TestCase
{
    public function testUnexpectedErrorsBecomeProblemResponsesOutsideDebug(): void
    {
        $event = $this->exceptionEvent(new \RuntimeException('Database credentials: secret'));

        new ProblemExceptionListener(debug: false)($event);

        $response = $event->getResponse();
        self::assertSame(500, $response->getStatusCode());
        self::assertSame('application/problem+json', $response->headers->get('Content-Type'));
        self::assertStringNotContainsString('secret', $response->getContent(), 'Exception messages must not leak.');
    }

    public function testUnexpectedErrorsKeepTheDebugPage(): void
    {
        $event = $this->exceptionEvent(new \RuntimeException('boom'));

        new ProblemExceptionListener(debug: true)($event);

        self::assertNull($event->getResponse());
    }

    private function exceptionEvent(\Throwable $exception): ExceptionEvent
    {
        return new ExceptionEvent(
            $this->createStub(HttpKernelInterface::class),
            new Request(),
            HttpKernelInterface::MAIN_REQUEST,
            $exception,
        );
    }
}
