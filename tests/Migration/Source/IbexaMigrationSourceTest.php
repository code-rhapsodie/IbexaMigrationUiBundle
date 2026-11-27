<?php

declare(strict_types=1);

namespace CodeRhapsodie\IbexaMigrationUiBundle\Tests\Migration\Source;

use CodeRhapsodie\IbexaMigrationUiBundle\Migration\MigrationCriteria;
use CodeRhapsodie\IbexaMigrationUiBundle\Migration\MigrationStatus;
use CodeRhapsodie\IbexaMigrationUiBundle\Migration\Source\IbexaMigrationSource;

final class IbexaMigrationSourceTest extends DatabaseTestCase
{
    public function testReadsIbexaMigrationsTable(): void
    {
        $this->connection->executeStatement('CREATE TABLE ibexa_migrations (name VARCHAR(191), executed_at DATETIME, execution_time DOUBLE PRECISION)');
        $this->connection->executeStatement(
            'INSERT INTO ibexa_migrations VALUES (?,?,?), (?,?,?)',
            ['2026_a.yaml', '2026-01-02 10:00:00', 0.42, '2026_b.yaml', '2026-01-03 10:00:00', null],
        );
        $source = new IbexaMigrationSource($this->connection, 'ibexa_migrations', true);

        self::assertTrue($source->isAvailable());
        $page = $source->search(new MigrationCriteria());
        self::assertSame(2, $page->total);
        self::assertSame('2026_b.yaml', $page->entries[0]->name);
        self::assertNull($page->entries[0]->executionTime);
        self::assertSame(0.42, $page->entries[1]->executionTime);
        self::assertSame(['done' => 2], $source->countByStatus());
        self::assertSame(1, $source->search(new MigrationCriteria('_a'))->total);
        self::assertSame(0, $source->search(new MigrationCriteria(null, MigrationStatus::Failed))->total);
    }

    public function testUnavailableWithoutTable(): void
    {
        self::assertFalse((new IbexaMigrationSource($this->connection, 'ibexa_migrations', true))->isAvailable());
    }
}
