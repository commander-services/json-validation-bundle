<?php

namespace Commander\JsonValidationBundle\JsonValidator;

use Opis\JsonSchema\Validator;
use Symfony\Component\Config\FileLocatorInterface;

class JsonValidator
{
    protected FileLocatorInterface $locator;

    protected string $schemaDir;

    protected array $errors = [];

    private Validator $validator;

    public function __construct(FileLocatorInterface $locator, string $schemaDir)
    {
        $this->locator   = $locator;
        $this->schemaDir = rtrim($schemaDir, DIRECTORY_SEPARATOR);
        $this->validator = new Validator();
    }

    public function validate(string $json, string $schemaPath)
    {
        $this->errors = [];

        try {
            $schemaFilePath = $this->locator->locate($schemaPath, $this->schemaDir);
            $schema = file_get_contents($schemaFilePath);
        } catch (\InvalidArgumentException $e) {
            $this->errors[] = sprintf('Unable to locate schema %s', $schemaPath);

            return null;
        }

        try {
            $data = json_decode($json, false, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $this->errors[] = sprintf('[%s] %s', $e->getCode(), $e->getMessage());

            return null;
        }

        try {
            $result = $this->validator->validate($data, $schema);

            if ($result->hasError()) {
                $this->errors[] = $result->error();

            return null;
        }
        } catch (\Throwable $e) {
            $this->errors[] = sprintf('[%s] %s', get_class($e), $e->getMessage());

            return null;
        }

        return $data;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
