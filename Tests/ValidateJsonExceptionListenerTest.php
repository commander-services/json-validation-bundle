<?php

namespace Tests;

use Commander\JsonValidationBundle\EventListener\ValidateJsonExceptionListener;
use Commander\JsonValidationBundle\Exception\JsonValidationRequestException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\Test\TestLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ValidateJsonExceptionListenerTest extends TestCase
{
    public function testNonJsonValidationException(): void
    {
        $event = $this->getEvent(new \RuntimeException('Not JsonValidationException'));

        $logger = new TestLogger();
        $listener = $this->createValidateJsonExceptionListener($event, $logger);

        $this->assertNull($event->getResponse());
        $this->assertFalse($logger->hasError('Json request validation'));
    }

    public function testEmptyErrors(): void
    {
        $event = $this->getEvent($this->createJsonValidationRequestException([]));

        $logger = new TestLogger();
        $listener = $this->createValidateJsonExceptionListener($event, $logger);

        $this->assertInstanceOf(Response::class, $event->getResponse());
        $this->assertTrue($event->getResponse()->headers->contains('Content-Type', 'application/problem+json'));

        $json = json_decode($event->getResponse()->getContent());
        $this->assertEquals([], $json->errors);
        $this->assertEquals(400, $json->status);
        $this->assertEquals('Unable to parse/validate JSON', $json->title);
        $this->assertEquals('There was a problem with the JSON that was sent with the request', $json->detail);
        $this->assertTrue($logger->hasError('Json request validation'));
    }

    public function testMessageOnlyError(): void
    {
        $event = $this->getEvent($this->createJsonValidationRequestException([['message' => 'Test message'],]));

        $logger = new TestLogger();
        $listener = $this->createValidateJsonExceptionListener($event, $logger);

        $json = json_decode($event->getResponse()->getContent(), true);

        $this->assertEquals([['message' => 'Test message']], $json['errors']);
        $this->assertTrue($logger->hasError('Json request validation'));
    }

    public function testConstraintError(): void
    {
        $event = $this->getEvent($this->createJsonValidationRequestException([
            [
                'constraint' => 'a',
                'property'   => 'b',
                'pointer'    => 'c',
                'message'    => 'd',
            ]
        ]));

        $listener = $this->createValidateJsonExceptionListener($event);

        $json = json_decode($event->getResponse()->getContent(), true);

        $this->assertEquals([
            [
                'constraint' => 'a',
                'property'   => 'b',
                'pointer'    => 'c',
                'message'    => 'd',
            ]
        ], $json['errors']);
    }

    public function testMixedErrors(): void
    {
        $event = $this->getEvent($this->createJsonValidationRequestException([
            ['message' => 'Test message'],
            [
                'constraint' => 'a',
                'property'   => 'b',
                'pointer'    => 'c',
                'message'    => 'd',
            ]
        ]));

        $listener = $this->createValidateJsonExceptionListener($event);

        $json = json_decode($event->getResponse()->getContent(), true);

        $this->assertEquals([
            ['message' => 'Test message'],
            [
                'constraint' => 'a',
                'property'   => 'b',
                'pointer'    => 'c',
                'message'    => 'd',
            ]
        ], $json['errors']);
    }

    protected function getEvent(\Throwable $exception): ExceptionEvent
    {
        $kernel      = $this->getMockBuilder(HttpKernelInterface::class)
                            ->getMock();
        $request     = Request::create('/');
        $requestType = HttpKernelInterface::MASTER_REQUEST;

        return new ExceptionEvent($kernel, $request, $requestType, $exception);
    }

    protected function createJsonValidationRequestException(array $errors = []): JsonValidationRequestException
    {
        return new JsonValidationRequestException(Request::create('/'), '/', $errors);
    }

    protected function createValidateJsonExceptionListener(ExceptionEvent $event, ?LoggerInterface $logger = null): ValidateJsonExceptionListener
    {
        $logger ??= new TestLogger();
        $listener = new ValidateJsonExceptionListener($logger);
        $listener->onKernelException($event);

        return $listener;
    }
}
