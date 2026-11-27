<?php

declare(strict_types=1);

namespace CodeRhapsodie\IbexaMigrationUiBundle\Tests\DependencyInjection;

use CodeRhapsodie\IbexaMigrationUiBundle\DependencyInjection\CodeRhapsodieIbexaMigrationUiExtension;
use CodeRhapsodie\IbexaMigrationUiBundle\DependencyInjection\Compiler\IbexaMigrationClassesPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class ExtensionTest extends TestCase
{
    /**
     * @param list<array<string, mixed>> $configs
     * @param list<array<string, mixed>> $doctrineMigrations
     * @param array<string, string>      $bundles
     */
    private function load(array $configs = [], array $doctrineMigrations = [], array $bundles = []): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.bundles', $bundles);
        $container->registerExtension(new CodeRhapsodieIbexaMigrationUiExtension());
        foreach ($doctrineMigrations as $cfg) {
            $container->prependExtensionConfig('doctrine_migrations', $cfg);
        }
        (new CodeRhapsodieIbexaMigrationUiExtension())->load($configs, $container);

        return $container;
    }

    public function testDefaults(): void
    {
        $c = $this->load();

        self::assertSame('doctrine_migration_versions', $c->getParameter('code_rhapsodie_migration_ui.doctrine.table'));
        self::assertTrue($c->hasDefinition(\CodeRhapsodie\IbexaMigrationUiBundle\Controller\MigrationController::class));
    }

    public function testDoctrineTableIsDetectedFromDoctrineMigrationsConfig(): void
    {
        $c = $this->load([], [['storage' => ['table_storage' => ['table_name' => 'my_versions']]]]);
        self::assertSame('my_versions', $c->getParameter('code_rhapsodie_migration_ui.doctrine.table'));
    }

    public function testSourcesAreDisabledWhenTheirBundleIsMissing(): void
    {
        $c = $this->load();

        foreach (['doctrine', 'ibexa_doctrine', 'ibexa_migrations', 'kaliop'] as $source) {
            self::assertFalse($c->getParameter("code_rhapsodie_migration_ui.$source.enabled"), $source);
        }
    }

    public function testSourcesAreEnabledWhenTheirBundleIsRegistered(): void
    {
        $c = $this->load([], [], [
            'DoctrineMigrationsBundle' => 'Doctrine\\Bundle\\MigrationsBundle\\DoctrineMigrationsBundle',
            'KaliopMigrationBundle' => 'Kaliop\\IbexaMigrationBundle\\KaliopMigrationBundle',
        ]);

        self::assertTrue($c->getParameter('code_rhapsodie_migration_ui.doctrine.enabled'));
        self::assertTrue($c->getParameter('code_rhapsodie_migration_ui.kaliop.enabled'));
        self::assertFalse($c->getParameter('code_rhapsodie_migration_ui.ibexa_doctrine.enabled'));
        self::assertFalse($c->getParameter('code_rhapsodie_migration_ui.ibexa_migrations.enabled'));
    }

    public function testCompilerPassCollectsTaggedIbexaMigrationClasses(): void
    {
        $c = new ContainerBuilder();
        $c->setDefinition('m1', (new Definition('Ibexa\\Migrations\\V1'))->addTag('ibexa.doctrine_migrations.migration'));
        $c->setDefinition('m2', (new Definition('\\Ibexa\\Migrations\\V2'))->addTag('ibexa.doctrine_migrations.migration'));
        $c->setDefinition('other', new Definition('App\\Other'));

        (new IbexaMigrationClassesPass())->process($c);

        self::assertSame(
            ['Ibexa\\Migrations\\V1', 'Ibexa\\Migrations\\V2'],
            $c->getParameter('code_rhapsodie_migration_ui.ibexa_migration_classes'),
        );
    }
}
