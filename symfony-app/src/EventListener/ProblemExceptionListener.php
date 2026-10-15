<?php

namespace App\EventListener;

use App\Service\Problem;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\Exception\ExtraAttributesException;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * Converts errors into problem+json responses: HTTP errors (404, 400 from
 * request mapping, ...), unknown request fields, and — outside debug mode —
 * any other exception as a 500.
 */
#[AsEventListener(KernelEvents::EXCEPTION)]
class ProblemExceptionListener
{
    public function __construct(
        #[Autowire('%kernel.debug%')]
        private readonly bool $debug,
    ) {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof ExtraAttributesException) {
            $event->setResponse(Problem::response(Response::HTTP_BAD_REQUEST, 'Validation failed.', [
                'violations' => array_map(
                    static fn (string $attribute) => ['propertyPath' => $attribute, 'message' => 'This field is not allowed.'],
                    $exception->getExtraAttributes(),
                ),
            ]));

            return;
        }

        if (!$exception instanceof HttpExceptionInterface) {
            // Keep Symfony's exception page while debugging.
            if (!$this->debug) {
                $event->setResponse(Problem::response(Response::HTTP_INTERNAL_SERVER_ERROR));
            }

            return;
        }

        $extra = [];
        $detail = $exception->getMessage();
        $previous = $exception->getPrevious();
        if ($previous instanceof NotEncodableValueException) {
            $detail = $previous->getMessage();
        } elseif ($previous instanceof ValidationFailedException) {
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
