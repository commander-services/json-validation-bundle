<?php

namespace Tests;

use Commander\JsonValidationBundle\EventListener\ValidateJsonExceptionListener;
use Commander\JsonValidationBundle\Exception\JsonValidationRequestException;
use Commander\JsonValidationBundle\JsonValidator\JsonValidator;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\Log\Test\TestLogger;
use Symfony\Component\Config\FileLocator;
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
        $event = $this->getEvent(new JsonValidationRequestException(Request::create('/'), '/', null));

        $logger = new TestLogger();
        $listener = $this->createValidateJsonExceptionListener($event, $logger);

        $this->assertInstanceOf(Response::class, $event->getResponse());
        $this->assertTrue($event->getResponse()->headers->contains('Content-Type', 'application/problem+json'));

        $json = $this->getDecodedResponseContent($event->getResponse());

        $this->assertEquals([], $json['errors']);
        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $json['status']);
        $this->assertEquals('Unable to parse/validate JSON', $json['title']);
        $this->assertEquals('There was a problem with the JSON that was sent with the request', $json['detail']);
        $this->assertTrue($logger->hasError('Json request validation'));
    }

    public function testMessageOnlyError(): void
    {
        $expectedErrorMessage = 'Test message';
        $event = $this->getEvent(new JsonValidationRequestException(Request::create('/'), '/', $expectedErrorMessage));

        $logger = new TestLogger();
        $listener = $this->createValidateJsonExceptionListener($event, $logger);

        $json = $this->getDecodedResponseContent($event->getResponse());

        $this->assertEquals($expectedErrorMessage, $json['errors'][0]);
        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $json['status']);
        $this->assertTrue($logger->hasError('Json request validation'));
    }

    public function testValidationError(): void
    {
        $event = $this->getEvent($this->createJsonValidationRequestException('Tests/schema-simple.json', <<<'JSON'
            {
                "test": "String breaking max length rule"
            }
        JSON));

        $listener = $this->createValidateJsonExceptionListener($event);

        $json = $this->getDecodedResponseContent($event->getResponse());

        $this->assertEquals([
            '/test' => [
                'Maximum string length is 10, found 31',
            ],
        ], $json['errors']);
    }

    protected function getEvent(\Throwable $exception): ExceptionEvent
    {
        $kernel      = $this->getMockBuilder(HttpKernelInterface::class)
                            ->getMock();
        $request     = Request::create('/');
        $requestType = HttpKernelInterface::MAIN_REQUEST;

        return new ExceptionEvent($kernel, $request, $requestType, $exception);
    }

    protected function createJsonValidationRequestException(string $schemaPath, string $content): JsonValidationRequestException
    {
        $projectDir = dirname(__DIR__);
        $locator = new FileLocator([$projectDir]);
        $validator = new JsonValidator($locator, $projectDir);

        $validator->validate($content, $schemaPath);

        return new JsonValidationRequestException(Request::create('/'), '/', $validator->getError());
    }

    protected function createValidateJsonExceptionListener(ExceptionEvent $event, ?LoggerInterface $logger = null): ValidateJsonExceptionListener
    {
        $logger ??= new NullLogger();
        $listener = new ValidateJsonExceptionListener($logger);
        $listener->onKernelException($event);

        return $listener;
    }

    protected function getDecodedResponseContent(Response $response): array
    {
        try {
            return json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return [];
        }
    }
}
