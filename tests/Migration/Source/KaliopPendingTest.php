<?php

declare(strict_types=1);

namespace CodeRhapsodie\IbexaMigrationUiBundle\Tests\Migration\Source;

use CodeRhapsodie\IbexaMigrationUiBundle\Migration\MigrationCriteria;
use CodeRhapsodie\IbexaMigrationUiBundle\Migration\MigrationStatus;
use CodeRhapsodie\IbexaMigrationUiBundle\Migration\Source\KaliopMigrationSource;

final class KaliopPendingTest extends DatabaseTestCase
{
    private KaliopMigrationSource $source;

    protected function setUp(): void
    {
        parent::setUp();
        $this->connection->executeStatement('CREATE TABLE kaliop_migrations (migration VARCHAR(255), path VARCHAR(4000), md5 VARCHAR(32), execution_date INTEGER, status INTEGER, execution_error VARCHAR(4000))');
        $this->connection->executeStatement('INSERT INTO kaliop_migrations VALUES (?,?,?,?,?,?)', ['a.yml', '/p/a.yml', 'x', 1760000000, 2, null]);
        $this->source = new KaliopMigrationSource(
            $this->connection,
            'kaliop_migrations',
            true,
            static fn (): array => ['a.yml' => '/p/a.yml', 'z.yml' => '/p/z.yml', 'b.yml' => '/p/b.yml'],
        );
    }

    public function testPendingAreFilesWithoutRow(): void
    {
        $names = array_map(static fn (\CodeRhapsodie\IbexaMigrationUiBundle\Migration\MigrationEntry $e): string => $e->name, $this->source->getPending());
        self::assertSame(['b.yml', 'z.yml'], $names);
    }

    public function testTodoFilterAndCount(): void
    {
        $page = $this->source->search(new MigrationCriteria('z', MigrationStatus::Todo));
        self::assertSame(1, $page->total);
        self::assertSame(MigrationStatus::Todo, $page->entries[0]->status);
        self::assertSame('/p/z.yml', $page->entries[0]->path);
        self::assertSame(2, $this->source->countByStatus()['todo']);
    }

    public function testNoProviderMeansNoPending(): void
    {
        $source = new KaliopMigrationSource($this->connection, 'kaliop_migrations', true);
        self::assertSame([], $source->getPending());
        self::assertArrayNotHasKey('todo', $source->countByStatus());
    }
}
