<?php

namespace Commander\JsonValidationBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Commander\JsonValidationBundle\DependencyInjection\JsonValidationExtension;

class JsonValidationBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function getContainerExtension()
    {
        return new JsonValidationExtension();
    }
}
