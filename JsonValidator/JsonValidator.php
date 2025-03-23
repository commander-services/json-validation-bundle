<?php

namespace Commander\JsonValidationBundle\JsonValidator;

use Opis\JsonSchema\Errors\ValidationError;
use Opis\JsonSchema\Validator;
use Symfony\Component\Config\FileLocatorInterface;

class JsonValidator
{
    protected FileLocatorInterface $locator;

    protected string $schemaDir;

    /**
     * @var null|string|ValidationError
     */
    protected $error = null;

    private Validator $validator;

    public function __construct(FileLocatorInterface $locator, string $schemaDir)
    {
        $this->locator   = $locator;
        $this->schemaDir = rtrim($schemaDir, DIRECTORY_SEPARATOR);
        $this->validator = new Validator();
    }

    public function validate(string $json, string $schemaPath)
    {
        $this->error = null;

        try {
            $schemaFilePath = $this->locator->locate($schemaPath, $this->schemaDir);
            $schema = file_get_contents($schemaFilePath);
        } catch (\InvalidArgumentException $e) {
            $this->error = sprintf('Unable to locate schema %s', $schemaPath);

            throw $e;
        }

        try {
            $data = json_decode($json, false, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $this->error = sprintf('[%s] %s', $e->getCode(), $e->getMessage());

            return null;
        }

        try {
            $result = $this->validator->validate($data, $schema);

            if ($result->hasError()) {
                $this->error = $result->error();

                return null;
            }
        } catch (\Throwable $e) {
            $this->error = sprintf('[%s] %s', get_class($e), $e->getMessage());

            return null;
        }

        return $data;
    }

    /**
     * @return null|string|ValidationError
     */
    public function getError()
    {
        return $this->error;
    }

    public function hasError(): bool
    {
        return $this->error !== null;
    }

    /**
     * @deprecated
     */
    public function getErrors(): array
    {
        return [$this->error];
    }
}
