<?php

declare(strict_types=1);

namespace CodeRhapsodie\IbexaMigrationUiBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Collects the classes of migrations registered through ibexa/doctrine-migrations,
 * so they can be told apart from the application's own Doctrine migrations
 * (both live in the same "doctrine_migration_versions" table).
 */
final class IbexaMigrationClassesPass implements CompilerPassInterface
{
    private const TAG = 'ibexa.doctrine_migrations.migration';

    public function process(ContainerBuilder $container): void
    {
        $classes = [];
        foreach ($container->findTaggedServiceIds(self::TAG) as $id => $tags) {
            $class = $container->getDefinition($id)->getClass() ?? $id;
            $classes[] = ltrim($container->getParameterBag()->resolveValue($class), '\\');
        }

        $container->setParameter('code_rhapsodie_migration_ui.ibexa_migration_classes', array_values(array_unique($classes)));
    }
}
