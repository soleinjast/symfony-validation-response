<?php

declare(strict_types=1);

namespace Soleinjast\ValidationResponse\Tests\EventListener;

use RuntimeException;
use PHPUnit\Framework\TestCase;
use Soleinjast\ValidationResponse\EventListener\ValidationExceptionListener;
use Soleinjast\ValidationResponse\Formatter\SimpleFormatter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final class ValidationExceptionListenerTest extends TestCase
{
    private ValidationExceptionListener $listener;

    protected function setUp(): void
    {
        $formatter = new SimpleFormatter();
        $this->listener = new ValidationExceptionListener($formatter, 422);
    }

    public function testGetSubscribedEvents(): void
    {
        $events = ValidationExceptionListener::getSubscribedEvents();

        $this->assertArrayHasKey(KernelEvents::EXCEPTION, $events);
        $this->assertSame(['onKernelException', 10], $events[KernelEvents::EXCEPTION]);
    }

    public function testHandlesValidationException(): void
    {
        // Create a validation violation
        $violation = new ConstraintViolation(
            'This field is required',
            'This field is required',
            [],
            null,
            'name',
            ''
        );

        $violations = new ConstraintViolationList([$violation]);
        $validationException = new ValidationFailedException(null, $violations);
        $exception = new UnprocessableEntityHttpException('Validation failed', $validationException);

        // Create event
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        $event = new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);

        // Handle exception
        $this->listener->onKernelException($event);

        // Assert response was set
        $this->assertTrue($event->hasResponse());

        $response = $event->getResponse();
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(422, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $content);
        $this->assertArrayHasKey('name', $content['errors']);
        $this->assertSame('This field is required', $content['errors']['name'][0]);
    }

    public function testIgnoresNonValidationExceptions(): void
    {
        $exception = new RuntimeException('Some other error');

        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        $event = new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);

        $this->listener->onKernelException($event);

        // Should not set a response
        $this->assertFalse($event->hasResponse());
    }

    public function testIgnoresUnprocessableEntityWithoutValidationException(): void
    {
        $exception = new UnprocessableEntityHttpException('Some error');

        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        $event = new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);

        $this->listener->onKernelException($event);

        // Should not set a response
        $this->assertFalse($event->hasResponse());
    }

    public function testCustomStatusCode(): void
    {
        $formatter = new SimpleFormatter();
        $listener = new ValidationExceptionListener($formatter, 400);

        $violation = new ConstraintViolation('Error', 'Error', [], null, 'field', '');
        $violations = new ConstraintViolationList([$violation]);
        $validationException = new ValidationFailedException(null, $violations);
        $exception = new UnprocessableEntityHttpException('Validation failed', $validationException);

        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        $event = new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);

        $listener->onKernelException($event);

        $response = $event->getResponse();
        $this->assertSame(400, $response->getStatusCode());
    }
}