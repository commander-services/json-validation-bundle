<?php

namespace Commander\JsonValidationBundle\EventListener;

use Commander\JsonValidationBundle\Annotation\ValidateJsonRequest;
use Commander\JsonValidationBundle\Exception\JsonValidationRequestException;
use Commander\JsonValidationBundle\JsonValidator\JsonValidator;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

class ValidateJsonRequestListener
{
    protected JsonValidator $jsonValidator;

    public function __construct(JsonValidator $jsonValidator)
    {
        $this->jsonValidator = $jsonValidator;
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $request = $event->getRequest();

        $annotationAlias = sprintf('_%s', ValidateJsonRequest::ALIAS);

        if (!$request->attributes->has($annotationAlias)) {
            return;
        }

        /** @var ValidateJsonRequest $annotation */
        $annotation = $request->attributes->get($annotationAlias);

        $httpMethods = array_map(fn(string $method): string => strtoupper($method), $annotation->getMethods());

        if (!empty($httpMethods) && !in_array($request->getMethod(), $httpMethods)) {
            return;
        }

        $content = $request->getContent();

        if ($annotation->getEmptyIsValid() && empty($content)) {
            return;
        }

        $objectData = $this->jsonValidator->validate(
            $content,
            $annotation->getPath()
        );

        if ($this->jsonValidator->hasError()) {
            throw new JsonValidationRequestException($request, $annotation->getPath(), $this->jsonValidator->getError());
        }

        if ($this->getAsArray($event->getController())) {
            $request->attributes->set('validJson', json_decode($content, true));
        } else {
            $request->attributes->set('validJson', $objectData);
        }
    }

    /**
     * Decide whether the validated JSON should be decoded as an array
     *
     * This is based upon the type hint for the $validJson argument
     *
     * @see \Sensio\Bundle\FrameworkExtraBundle\EventListener\ParamConverterListener::onKernelController
     */
    protected function getAsArray($controller): bool
    {
        $r = null;

        if (is_array($controller)) {
            $r = new \ReflectionMethod($controller[0], $controller[1]);
        } elseif (is_object($controller) && is_callable($controller, '__invoke')) {
            $r = new \ReflectionMethod($controller, '__invoke');
        } else {
            $r = new \ReflectionFunction($controller);
        }

        foreach ($r->getParameters() as $param) {
            if ($param->getName() !== 'validJson') {
                continue;
            }

            return $param->isArray();
        }

        return false;
    }
}
