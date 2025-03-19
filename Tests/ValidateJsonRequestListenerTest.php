<?php

namespace Tests;

use Commander\JsonValidationBundle\Annotation\ValidateJsonRequest;
use Commander\JsonValidationBundle\EventListener\ValidateJsonRequestListener;
use Commander\JsonValidationBundle\Exception\JsonValidationRequestException;
use Commander\JsonValidationBundle\JsonValidator\JsonValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ValidateJsonRequestListenerTest extends TestCase
{
    public function testMissingAttribute(): void
    {
        $request  = new Request();
        $event    = $this->getControllerEvent($request);
        $listener = $this->getValidateJsonListener();

        $listener->onKernelController($event);

        $this->assertFalse($request->attributes->has('validJson'));
    }

    public function testInvalidMethod(): void
    {
        $annotation = new ValidateJsonRequest(['path' => 'schema-simple.json', 'methods' => ['POST']]);

        $request = Request::create('/');
        // in the real system this is handled by SensioFrameworkExtraBundle
        $request->attributes->set(sprintf('_%s', ValidateJsonRequest::ALIAS), $annotation);

        $event    = $this->getControllerEvent($request);
        $listener = $this->getValidateJsonListener();

        $listener->onKernelController($event);

        $this->assertFalse($request->attributes->has('validJson'));
    }

    public function testInvalidJson(): void
    {
        $annotation = new ValidateJsonRequest(['path' => 'schema-simple.json']);

        $request = Request::create('/', Request::METHOD_POST, [], [], [], [], '{invalid');
        $request->attributes->set(sprintf('_%s', ValidateJsonRequest::ALIAS), $annotation);

        $event    = $this->getControllerEvent($request);
        $listener = $this->getValidateJsonListener();

        $this->expectException(JsonValidationRequestException::class);
        $listener->onKernelController($event);
    }

    public function testValidJson(): void
    {
        $annotation = new ValidateJsonRequest(['path' => 'schema-simple.json']);

        $request = Request::create('/', Request::METHOD_POST, [], [], [], [], '{"test": "hello"}');
        $request->attributes->set(sprintf('_%s', ValidateJsonRequest::ALIAS), $annotation);

        $event    = $this->getControllerEvent($request);
        $listener = $this->getValidateJsonListener();

        $listener->onKernelController($event);

        $this->assertTrue($request->attributes->has('validJson'));
        $this->assertEquals($request->attributes->get('validJson')->test, 'hello');
    }

    public function testValidJsonArray(): void
    {
        $annotation = new ValidateJsonRequest(['path' => 'schema-simple.json']);
        $request    = Request::create('/', Request::METHOD_POST, [], [], [], [], '{"test": "hello"}');
        $request->attributes->set(sprintf('_%s', ValidateJsonRequest::ALIAS), $annotation);

        $kernel     = $this->getMockBuilder(HttpKernelInterface::class)
                           ->getMock();
        $controller = function (array $validJson): void {
        };
        $type       = HttpKernelInterface::MASTER_REQUEST;
        $event      = new ControllerEvent($kernel, $controller, $request, $type);

        $listener = $this->getValidateJsonListener();

        $listener->onKernelController($event);

        $this->assertTrue($request->attributes->has('validJson'));
        $this->assertTrue(is_array($request->attributes->get('validJson')));
        $this->assertEquals($request->attributes->get('validJson')['test'], 'hello');
    }

    protected function getValidateJsonListener(): ValidateJsonRequestListener
    {
        $locator   = new FileLocator([__DIR__]);
        $validator = new JsonValidator($locator, __DIR__);

        return new ValidateJsonRequestListener($validator);
    }

    protected function getControllerEvent(Request $request): ControllerEvent
    {
        $kernel     = $this->getMockBuilder(HttpKernelInterface::class)
                           ->getMock();
        $controller = function ($validJson): void {
        };
        $type       = HttpKernelInterface::MASTER_REQUEST;

        return new ControllerEvent($kernel, $controller, $request, $type);
    }
}
