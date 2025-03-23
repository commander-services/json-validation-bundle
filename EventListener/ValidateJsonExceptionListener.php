<?php

namespace Commander\JsonValidationBundle\EventListener;

use Commander\JsonValidationBundle\Exception\JsonValidationRequestException;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Errors\ValidationError;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

class ValidateJsonExceptionListener
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if (!$exception instanceof JsonValidationRequestException) {
            return;
        }

        $data = [
            'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
            'title' => 'Unable to parse/validate JSON',
            'detail' => 'There was a problem with the JSON that was sent with the request',
            'errors' => $this->formatErrors($exception->getError()),
        ];

        $event->setResponse(
            new JsonResponse(
                $data,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                ['Content-Type' => 'application/problem+json']
            )
        );

        $this->logger->error(
            'Json request validation',
            [
                'uri' => $exception->getRequest()->getUri(),
                'schemaPath' => $exception->getSchemaPath(),
                'errors' => $exception->getError(),
            ]
        );
    }

    protected function formatErrors($error): array
    {
        if ($error instanceof ValidationError) {
            return (new ErrorFormatter())->format($error, true);
        }

        if (empty($error)) {
            return [];
        }
        
        return [(string) $error];
    }
}
