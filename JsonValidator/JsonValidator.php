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

    public function __construct(
        FileLocatorInterface $locator,
        string $schemaDir,
        array $resolverRegisterFiles = [],
        array $resolverRegisterPrefixes = []
    ) {
        $this->locator   = $locator;
        $this->schemaDir = rtrim($schemaDir, DIRECTORY_SEPARATOR);
        //TODO this is ugly, refactor to factory
        $this->validator = $this->createValidator($resolverRegisterFiles, $resolverRegisterPrefixes);
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

    /**
     * @param array<string,string> $resolverRegisterFiles
     * @param array<string,string> $resolverRegisterPrefixes
     */
    private function createValidator(array $resolverRegisterFiles, array $resolverRegisterPrefixes): Validator
    {
        $validator = new Validator();

        if (($resolver = $validator->loader()->resolver()) !== null) {
            foreach ($resolverRegisterFiles as $id => $file) {
                $resolver->registerFile($id, $file);
            }

            foreach ($resolverRegisterPrefixes as $prefix => $dir) {
                $resolver->registerPrefix($prefix, $dir);
            }
        }

        return $validator;
    }
}
