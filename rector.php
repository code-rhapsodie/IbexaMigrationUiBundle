<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return RectorConfig::configure()
    ->withPaths([__DIR__ . '/src', __DIR__ . '/tests'])
    ->withSets([LevelSetList::UP_TO_PHP_81, SetList::CODE_QUALITY, SetList::DEAD_CODE, SetList::TYPE_DECLARATION])
    // Optional bundles are referenced by name: ::class would fail static analysis when they are not installed.
    ->withSkip([StringClassNameToClassConstantRector::class => [__DIR__ . '/src/DependencyInjection', __DIR__ . '/tests/DependencyInjection']]);
