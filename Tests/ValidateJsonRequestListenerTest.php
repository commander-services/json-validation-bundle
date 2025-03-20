<?php

namespace Tests;

use Commander\JsonValidationBundle\Annotation\ValidateJsonRequest;
use Commander\JsonValidationBundle\EventListener\ValidateJsonRequestListener;
use Commander\JsonValidationBundle\Exception\JsonValidationRequestException;
use Commander\JsonValidationBundle\JsonValidator\JsonValidator;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\Log\Test\TestLogger;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ValidateJsonRequestListenerTest extends TestCase
{
    public function testMissingAttribute(): void
    {
        $request = new Request();

        $event = $this->createControllerEvent($request);

        $listener = $this->createValidateJsonListener($event);

        $this->assertFalse($request->attributes->has('validJson'));
    }

    public function testInvalidMethod(): void
    {
        $annotation = new ValidateJsonRequest(['path' => 'Tests/schema-simple.json', 'methods' => [Request::METHOD_POST]]);

        $request = Request::create('/');
        $request->attributes->set(sprintf('_%s', ValidateJsonRequest::ALIAS), $annotation);

        $event = $this->createControllerEvent($request);

        $listener = $this->createValidateJsonListener($event);

        $this->assertFalse($request->attributes->has('validJson'));
    }

    public function testInvalidSchema(): void
    {
        $annotation = new ValidateJsonRequest(['path' => 'Tests/schema-invalid.json']);

        $request = Request::create('/', Request::METHOD_POST, [], [], [], [], '{"test": "hello"}');
        $request->attributes->set(sprintf('_%s', ValidateJsonRequest::ALIAS), $annotation);

        $event = $this->createControllerEvent($request);

        $this->expectException(JsonValidationRequestException::class);

        $listener = $this->createValidateJsonListener($event);

        $this->assertFalse($request->attributes->has('validJson'));
    }

    public function testInvalidJson(): void
    {
        $annotation = new ValidateJsonRequest(['path' => 'Tests/schema-simple.json']);

        $request = Request::create('/', Request::METHOD_POST, [], [], [], [], '{invalid');
        $request->attributes->set(sprintf('_%s', ValidateJsonRequest::ALIAS), $annotation);

        $event = $this->createControllerEvent($request);

        $this->expectException(JsonValidationRequestException::class);

        $listener = $this->createValidateJsonListener($event);

        $this->assertFalse($request->attributes->has('validJson'));
    }

    public function testValidJson(): void
    {
        $annotation = new ValidateJsonRequest(['path' => 'Tests/schema-simple.json']);

        $request = Request::create('/', Request::METHOD_POST, [], [], [], [], '{"test": "hello"}');
        $request->attributes->set(sprintf('_%s', ValidateJsonRequest::ALIAS), $annotation);

        $event = $this->createControllerEvent($request, function ($validJson): void {
        });

        $listener = $this->createValidateJsonListener($event);

        $this->assertTrue($request->attributes->has('validJson'));

        $actualValidJson = $request->attributes->get('validJson');
        $this->assertIsNotArray($actualValidJson);
        $this->assertEquals('hello', $actualValidJson->test);
    }

    public function testValidJsonArray(): void
    {
        $annotation = new ValidateJsonRequest(['path' => 'Tests/schema-simple.json']);

        $request = Request::create('/', Request::METHOD_POST, [], [], [], [], '{"test": "hello"}');
        $request->attributes->set(sprintf('_%s', ValidateJsonRequest::ALIAS), $annotation);

        $event = $this->createControllerEvent($request, function (array $validJson): void {
        });

        $listener = $this->createValidateJsonListener($event);

        $this->assertTrue($request->attributes->has('validJson'));

        $actualValidJson = $request->attributes->get('validJson');
        $this->assertIsArray($actualValidJson);
        $this->assertEquals('hello', $actualValidJson['test']);
    }

    public function testValidVendorJson(): void
    {
        $annotation = new ValidateJsonRequest(['path' => 'vendor/json-schema-org/json-schema-test-suite/test-schema.json']);

        $request = Request::create('/', 'POST', [], [], [], [], <<<'JSON'
            [{
                "description": "The test case description",
                "schema": { "type": "string" },
                "tests": [
                    {
                        "description": "a test with a valid instance",
                        "data": "a string",
                        "valid": true
                    }
                ]
            }]
        JSON);
        $request->attributes->set(sprintf('_%s', ValidateJsonRequest::ALIAS), $annotation);

        $event = $this->createControllerEvent($request);

        $listener = $this->createValidateJsonListener($event);

        $this->assertTrue($request->attributes->has('validJson'));

        $actualValidJson = $request->attributes->get('validJson');
        $this->assertIsArray($actualValidJson);
        $this->assertIsObject($actualValidJson[0]);
        $this->assertEquals('The test case description', $actualValidJson[0]->description);
    }

    protected function createValidateJsonListener(ControllerEvent $event): ValidateJsonRequestListener
    {
        $locator = new FileLocator([__DIR__]);
        $validator = new JsonValidator($locator, dirname(__DIR__));

        $listener = new ValidateJsonRequestListener($validator);
        $listener->onKernelController($event);

        return $listener;
    }

    protected function createControllerEvent(Request $request, ?callable $controller = null): ControllerEvent
    {
        $kernel = $this->getMockBuilder(HttpKernelInterface::class)->getMock();
        $controller ??= function ($validJson): void {
        };

        return new ControllerEvent($kernel, $controller, $request, HttpKernelInterface::MAIN_REQUEST);
    }
}
