<?php

namespace Commander\JsonValidationBundle\Exception;

use Opis\JsonSchema\Errors\ValidationError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class JsonValidationRequestException extends BadRequestHttpException
{
    /**
     * @var null|string|ValidationError
     */
    protected $error;

    protected Request $request;

    protected string $schemaPath;

    /**
     * @param null|string|ValidationError $error
     */
    public function __construct(Request $request, string $schemaPath, $error = null)
    {
        $this->request = $request;
        $this->schemaPath = $schemaPath;
        $this->error = $error;

        parent::__construct('Json request validation error');
    }

    /**
     * @deprecated
     */
    public function getErrors(): array
    {
        return [$this->error];
    }

    public function getError()
    {
        return $this->error;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getSchemaPath(): string
    {
        return $this->schemaPath;
    }
}
