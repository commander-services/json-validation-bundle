<?php

namespace Tests;

use Commander\JsonValidationBundle\Annotation\ValidateJsonResponse;
use Commander\JsonValidationBundle\EventListener\ValidateJsonResponseListener;
use Commander\JsonValidationBundle\JsonValidator\JsonValidator;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
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
        $annotation = new ValidateJsonResponse(['path' => 'schema-simple.json', 'statuses' => [Response::HTTP_OK]]);

        $request = Request::create('/');
        $request->attributes->set(sprintf('_%s', ValidateJsonResponse::ALIAS), $annotation);

        $response = new Response('', Response::HTTP_CREATED);

        $event = $this->getResponseEvent($request, $response);

        $logger = new TestLogger();
        $listener = $this->createValidateJsonResponseListener($event, $logger);

        $this->assertFalse($logger->hasWarning('Json response validation'));
    }

    public function testInvalidJson(): void
    {
        $annotation = new ValidateJsonResponse(['path' => 'schema-simple.json', 'statuses' => [Response::HTTP_OK]]);

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
        $annotation = new ValidateJsonResponse(['path' => 'schema-simple.json', 'statuses' => [Response::HTTP_OK]]);

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
        $locator = new FileLocator([__DIR__]);
        $validator = new JsonValidator($locator, __DIR__);
        $logger ??= new TestLogger();

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
