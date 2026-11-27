<?php

declare(strict_types=1);

namespace CodeRhapsodie\IbexaMigrationUiBundle\Migration\Source\Provider;

use Doctrine\Migrations\DependencyFactory;

/** Class names of the migrations registered in the "migrations_paths" of doctrine/doctrine-migrations-bundle. */
final class RegisteredDoctrineMigrations
{
    public function __construct(private readonly ?DependencyFactory $dependencyFactory = null)
    {
    }

    /** @return list<string> */
    public function __invoke(): array
    {
        if (!$this->dependencyFactory instanceof DependencyFactory) {
            return [];
        }

        $classes = [];
        foreach ($this->dependencyFactory->getMigrationRepository()->getMigrations()->getItems() as $migration) {
            $classes[] = ltrim($migration->getVersion()->__toString(), '\\');
        }

        return $classes;
    }
}
