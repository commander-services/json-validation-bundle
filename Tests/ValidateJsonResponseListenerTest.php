<?php

namespace Tests;

use Commander\JsonValidationBundle\Annotation\ValidateJsonResponse;
use Commander\JsonValidationBundle\EventListener\ValidateJsonResponseListener;
use Commander\JsonValidationBundle\JsonValidator\JsonValidator;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\Log\Test\TestLogger;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ValidateJsonResponseListenerTest extends TestCase
{
    public function testInvalidStatus(): void
    {
        $annotation = new ValidateJsonResponse(['path' => 'Tests/schema-simple.json', 'statuses' => [Response::HTTP_OK]]);

        $request = Request::create('/');
        $request->attributes->set(sprintf('_%s', ValidateJsonResponse::ALIAS), $annotation);

        $response = new Response('', Response::HTTP_CREATED);

        $event = $this->getResponseEvent($request, $response);

        $logger = new TestLogger();
        $listener = $this->createValidateJsonResponseListener($event, $logger);

        $this->assertFalse($logger->hasWarning('Json response validation'));
    }

    public function testInvalidSchemaPath(): void
    {
        $annotation = new ValidateJsonResponse(['path' => 'file/not/exists.json']);

        $request = Request::create('/');
        $request->attributes->set(sprintf('_%s', ValidateJsonResponse::ALIAS), $annotation);

        $response = new Response('{invalid', Response::HTTP_OK);

        $event = $this->getResponseEvent($request, $response);

        $this->expectException(\InvalidArgumentException::class);

        $listener = $this->createValidateJsonResponseListener($event);
    }

    public function testInvalidSchema(): void
    {
        $annotation = new ValidateJsonResponse(['path' => 'Tests/schema-invalid.json']);

        $request = Request::create('/');
        $request->attributes->set(sprintf('_%s', ValidateJsonResponse::ALIAS), $annotation);

        $response = new Response('{invalid', Response::HTTP_OK);

        $event = $this->getResponseEvent($request, $response);

        $logger = new TestLogger();
        $listener = $this->createValidateJsonResponseListener($event, $logger);

        $this->assertTrue($logger->hasWarning('Json response validation'));
    }

    public function testInvalidJson(): void
    {
        $annotation = new ValidateJsonResponse(['path' => 'Tests/schema-simple.json', 'statuses' => [Response::HTTP_OK]]);

        $request = Request::create('/');
        $request->attributes->set(sprintf('_%s', ValidateJsonResponse::ALIAS), $annotation);

        $response = new Response('{invalid', Response::HTTP_OK);

        $event = $this->getResponseEvent($request, $response);

        $logger = new TestLogger();
        $listener = $this->createValidateJsonResponseListener($event, $logger);

        $this->assertTrue($logger->hasWarning('Json response validation'));
    }

    public function testValidJson(): void
    {
        $annotation = new ValidateJsonResponse(['path' => 'Tests/schema-simple.json', 'statuses' => [Response::HTTP_OK]]);

        $request = Request::create('/');
        $request->attributes->set(sprintf('_%s', ValidateJsonResponse::ALIAS), $annotation);

        $response = new Response('{"test": "hello"}', Response::HTTP_OK);

        $event = $this->getResponseEvent($request, $response);

        $logger = new TestLogger();
        $listener = $this->createValidateJsonResponseListener($event, $logger);

        $this->assertFalse($logger->hasWarning('Json response validation'));
    }

    protected function createValidateJsonResponseListener(ResponseEvent $event, ?LoggerInterface $logger = null): ValidateJsonResponseListener
    {
        $projectDir = dirname(__DIR__);
        $locator = new FileLocator([$projectDir]);
        $validator = new JsonValidator($locator, $projectDir);
        $logger ??= new NullLogger();

        $listener = new ValidateJsonResponseListener($validator, $logger);
        $listener->onKernelResponse($event);

        return $listener;
    }

    protected function getResponseEvent(Request $request, Response $response): ResponseEvent
    {
        $kernel = $this->getMockBuilder(HttpKernelInterface::class)->getMock();
        $type = HttpKernelInterface::MAIN_REQUEST;

        return new ResponseEvent($kernel, $request, $type, $response);
    }
}
