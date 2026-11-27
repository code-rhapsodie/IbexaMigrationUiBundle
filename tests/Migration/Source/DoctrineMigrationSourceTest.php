<?php

declare(strict_types=1);

namespace CodeRhapsodie\IbexaMigrationUiBundle\Tests\Migration\Source;

use CodeRhapsodie\IbexaMigrationUiBundle\Migration\MigrationCriteria;
use CodeRhapsodie\IbexaMigrationUiBundle\Migration\MigrationStatus;
use CodeRhapsodie\IbexaMigrationUiBundle\Migration\Source\DoctrineMigrationSource;
use CodeRhapsodie\IbexaMigrationUiBundle\Migration\Source\IbexaDoctrineMigrationSource;

final class DoctrineMigrationSourceTest extends DatabaseTestCase
{
    private const IBEXA = 'Ibexa\\Migrations\\Version1';

    protected function setUp(): void
    {
        parent::setUp();
        $this->connection->executeStatement('CREATE TABLE doctrine_migration_versions (version VARCHAR(191) PRIMARY KEY, executed_at DATETIME, execution_time INTEGER)');
        $this->connection->executeStatement(
            'INSERT INTO doctrine_migration_versions VALUES (?, ?, ?), (?, ?, ?), (?, ?, ?)',
            ['App\\V1', '2026-01-01 10:00:00', 2, 'App\\V2', '2026-02-01 10:00:00', 1, self::IBEXA, '2026-01-15 10:00:00', 3],
        );
    }

    public function testListsAllMigrationsNewestFirstWhenNoIbexaClass(): void
    {
        $source = new DoctrineMigrationSource($this->connection, 'doctrine_migration_versions', true);
        $page = $source->search(new MigrationCriteria());

        self::assertSame(3, $page->total);
        self::assertSame(['App\\V2', self::IBEXA, 'App\\V1'], array_map(static fn (\CodeRhapsodie\IbexaMigrationUiBundle\Migration\MigrationEntry $e): string => $e->name, $page->entries));
        self::assertSame(MigrationStatus::Done, $page->entries[0]->status);
        self::assertSame('2026-02-01', $page->entries[0]->executedAt?->format('Y-m-d'));
        self::assertSame(1.0, $page->entries[0]->executionTime);
        self::assertSame(['done' => 3], $source->countByStatus());
    }

    public function testExcludesIbexaMigrationsAndIbexaSourceKeepsOnlyThem(): void
    {
        $app = new DoctrineMigrationSource($this->connection, 'doctrine_migration_versions', true, [self::IBEXA]);
        $ibexa = new IbexaDoctrineMigrationSource($this->connection, 'doctrine_migration_versions', true, [self::IBEXA]);

        self::assertSame(2, $app->search(new MigrationCriteria())->total);
        $page = $ibexa->search(new MigrationCriteria());
        self::assertSame(1, $page->total);
        self::assertSame(self::IBEXA, $page->entries[0]->name);
    }

    public function testDescriptionComesFromTheMigrationClass(): void
    {
        $this->connection->executeStatement(
            'INSERT INTO doctrine_migration_versions VALUES (?, ?, ?)',
            [DescribedMigration::class, '2026-03-01 10:00:00', 1],
        );

        $entries = (new DoctrineMigrationSource($this->connection, 'doctrine_migration_versions', true))->search(new MigrationCriteria())->entries;

        self::assertSame(DescribedMigration::class, $entries[0]->name);
        self::assertSame('Adds the foo table', $entries[0]->description);
        self::assertSame('5.0.0', $entries[0]->targetVersion);
        self::assertNull($entries[1]->targetVersion);
        self::assertNull($entries[1]->description);
    }

    public function testSearchAndPagination(): void
    {
        $source = new DoctrineMigrationSource($this->connection, 'doctrine_migration_versions', true);

        self::assertSame(2, $source->search(new MigrationCriteria('App'))->total);
        $page = $source->search(new MigrationCriteria(null, null, 1, 1));
        self::assertSame(3, $page->total);
        self::assertCount(1, $page->entries);
        self::assertSame(self::IBEXA, $page->entries[0]->name);
    }

    public function testSearchEscapesLikeWildcards(): void
    {
        $source = new DoctrineMigrationSource($this->connection, 'doctrine_migration_versions', true);
        self::assertSame(0, $source->search(new MigrationCriteria('%'))->total);
    }

    public function testNonDoneStatusFilterReturnsNothing(): void
    {
        $source = new DoctrineMigrationSource($this->connection, 'doctrine_migration_versions', true);

        self::assertSame(0, $source->search(new MigrationCriteria(null, MigrationStatus::Failed))->total);
        self::assertSame(3, $source->search(new MigrationCriteria(null, MigrationStatus::Done))->total);
    }

    public function testAvailability(): void
    {
        self::assertTrue((new DoctrineMigrationSource($this->connection, 'doctrine_migration_versions', true))->isAvailable());
        self::assertFalse((new DoctrineMigrationSource($this->connection, 'doctrine_migration_versions', false))->isAvailable());
        self::assertFalse((new DoctrineMigrationSource($this->connection, 'missing_table', true))->isAvailable());
        // Ibexa source is unavailable when no Ibexa migration is registered.
        self::assertFalse((new IbexaDoctrineMigrationSource($this->connection, 'doctrine_migration_versions', true, []))->isAvailable());
        self::assertTrue((new IbexaDoctrineMigrationSource($this->connection, 'doctrine_migration_versions', true, [self::IBEXA]))->isAvailable());
    }
}

final class DescribedMigration
{
    public function getDescription(): string
    {
        return ' Adds the foo table ';
    }

    public static function getTargetVersion(): string
    {
        return '5.0.0';
    }
}
