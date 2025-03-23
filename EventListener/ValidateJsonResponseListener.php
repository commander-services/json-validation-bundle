<?php

namespace Commander\JsonValidationBundle\EventListener;

use Commander\JsonValidationBundle\Annotation\ValidateJsonResponse;
use Commander\JsonValidationBundle\JsonValidator\JsonValidator;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class ValidateJsonResponseListener
{
    protected JsonValidator $jsonValidator;

    protected LoggerInterface $logger;

    public function __construct(JsonValidator $jsonValidator, LoggerInterface $logger)
    {
        $this->jsonValidator = $jsonValidator;
        $this->logger        = $logger;
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request  = $event->getRequest();
        $response = $event->getResponse();

        $annotationAlias = sprintf('_%s', ValidateJsonResponse::ALIAS);

        if (!$request->attributes->has($annotationAlias)) {
            return;
        }

        /** @var ValidateJsonResponse $annotation */
        $annotation = $request->attributes->get($annotationAlias);

        if (!empty($annotation->getStatuses()) && !in_array($response->getStatusCode(), $annotation->getStatuses())) {
            return;
        }

        $content = $response->getContent();

        if ($annotation->getEmptyIsValid() && empty($content)) {
            return;
        }

        $this->jsonValidator->validate(
            $content,
            $annotation->getPath()
        );

        if ($this->jsonValidator->hasError()) {
            $this->logger->warning('Json response validation',
                [
                    'uri'        => $request->getUri(),
                    'schemaPath' => $annotation->getPath(),
                    'errors'     => $this->jsonValidator->getError()
                ]
            );
        }
    }
}
