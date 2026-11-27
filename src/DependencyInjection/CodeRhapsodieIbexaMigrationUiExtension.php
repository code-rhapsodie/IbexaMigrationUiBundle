<?php

declare(strict_types=1);

namespace CodeRhapsodie\IbexaMigrationUiBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

final class CodeRhapsodieIbexaMigrationUiExtension extends Extension
{
    public function getAlias(): string
    {
        return 'code_rhapsodie_ibexa_migration_ui';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $bundles = $container->hasParameter('kernel.bundles') ? array_values((array) $container->getParameter('kernel.bundles')) : [];
        $has = static fn (string ...$classes): bool => array_intersect($classes, $bundles) !== [];

        // Bundle classes are plain strings on purpose: these bundles are optional, so they may not exist.
        // Each source is enabled only when the bundle that owns its table is registered in the kernel.
        $container->setParameter('code_rhapsodie_migration_ui.doctrine.enabled', $has('Doctrine\\Bundle\\MigrationsBundle\\DoctrineMigrationsBundle'));
        $container->setParameter('code_rhapsodie_migration_ui.doctrine.table', $this->detectDoctrineMigrationsTable($container) ?? 'doctrine_migration_versions');
        $container->setParameter('code_rhapsodie_migration_ui.ibexa_doctrine.enabled', $has('Ibexa\\Bundle\\DoctrineMigrations\\IbexaDoctrineMigrationsBundle'));
        $container->setParameter('code_rhapsodie_migration_ui.ibexa_migrations.enabled', $has('Ibexa\\Bundle\\Migration\\IbexaMigrationBundle'));
        $container->setParameter('code_rhapsodie_migration_ui.kaliop.enabled', $has(
            'Kaliop\\IbexaMigrationBundle\\KaliopMigrationBundle',
            'Kaliop\\eZMigrationBundle\\eZMigrationBundle',
        ));
        // Filled by IbexaMigrationClassesPass.
        $container->setParameter('code_rhapsodie_migration_ui.ibexa_migration_classes', []);

        (new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config')))->load('services.yaml');
    }

    private function detectDoctrineMigrationsTable(ContainerBuilder $container): ?string
    {
        foreach (array_reverse($container->getExtensionConfig('doctrine_migrations')) as $cfg) {
            $table = $cfg['storage']['table_storage']['table_name'] ?? null;
            if (\is_string($table) && $table !== '') {
                return $table;
            }
        }

        return null;
    }
}
