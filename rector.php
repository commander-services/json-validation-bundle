<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\Symfony\Set\SymfonySetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/Annotation',
        __DIR__ . '/DependencyInjection',
        __DIR__ . '/EventListener',
        __DIR__ . '/Exception',
        __DIR__ . '/JsonValidator',
        __DIR__ . '/Tests',
    ])
    // uncomment to reach your current PHP version
    // ->withPhpSets()
    ->withSets([
        LevelSetList::UP_TO_PHP_74,
        SymfonySetList::SYMFONY_54,
        SymfonySetList::SYMFONY_CODE_QUALITY,
        SetList::TYPE_DECLARATION,
    ])
    ->withDeadCodeLevel(0)
    ->withCodeQualityLevel(0)
;
