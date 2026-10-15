<?php

namespace App\EventListener;

use App\Service\Problem;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * Converts HTTP errors (404, 400 from request mapping, ...) into problem+json responses.
 */
#[AsEventListener(KernelEvents::EXCEPTION)]
class ProblemExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        if (!$exception instanceof HttpExceptionInterface) {
            return;
        }

        $extra = [];
        $detail = $exception->getMessage();
        $previous = $exception->getPrevious();
        if ($previous instanceof ValidationFailedException) {
            $detail = 'Validation failed.';
            $extra['violations'] = array_map(
                static fn (ConstraintViolationInterface $violation) => [
                    'propertyPath' => $violation->getPropertyPath(),
                    'message' => $violation->getMessage(),
                ],
                iterator_to_array($previous->getViolations()),
            );
        }

        $event->setResponse(Problem::response($exception->getStatusCode(), $detail, $extra, $exception->getHeaders()));
    }
}
