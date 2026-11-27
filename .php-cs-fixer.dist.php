<?php

declare(strict_types=1);

$finder = (new PhpCsFixer\Finder())->in([__DIR__ . '/src', __DIR__ . '/tests']);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'declare_strict_types' => true,
        'concat_space' => ['spacing' => 'one'],
        'yoda_style' => false,
        'phpdoc_to_comment' => false,
        'global_namespace_import' => ['import_classes' => true, 'import_functions' => false, 'import_constants' => false],
    ])
    ->setFinder($finder);
