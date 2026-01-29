<?php

declare(strict_types=1);

namespace Soleinjast\ValidationResponse\EventListener;

use Soleinjast\ValidationResponse\Formatter\FormatterInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final readonly class ValidationExceptionListener implements EventSubscriberInterface
{
    public function __construct(
        private FormatterInterface $formatter,
        private int $statusCode,
    )
    {

    }

    public static function getSubscribedEvents(): array
    {
        return [
          KernelEvents::EXCEPTION => ['onKernelException', 10],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void{
        $exception = $event->getThrowable();

        if (!$exception instanceof UnprocessableEntityHttpException) {
            return;
        }

        $previous = $exception->getPrevious();
        if (!$previous instanceof ValidationFailedException) {
            return;
        }

        $violations = $previous->getViolations();
        $data = $this->formatter->format($violations);

        $response = new JsonResponse($data, $this->statusCode);

        $event->setResponse($response);
    }
}