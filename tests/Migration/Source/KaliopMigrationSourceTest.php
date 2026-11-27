<?php

declare(strict_types=1);

namespace CodeRhapsodie\IbexaMigrationUiBundle\Tests\Migration\Source;

use CodeRhapsodie\IbexaMigrationUiBundle\Migration\MigrationCriteria;
use CodeRhapsodie\IbexaMigrationUiBundle\Migration\MigrationStatus;
use CodeRhapsodie\IbexaMigrationUiBundle\Migration\Source\KaliopMigrationSource;

final class KaliopMigrationSourceTest extends DatabaseTestCase
{
    private KaliopMigrationSource $source;

    protected function setUp(): void
    {
        parent::setUp();
        $this->connection->executeStatement('CREATE TABLE kaliop_migrations (migration VARCHAR(255), path VARCHAR(4000), md5 VARCHAR(32), execution_date INTEGER, status INTEGER, execution_error VARCHAR(4000))');
        $this->connection->executeStatement(
            'INSERT INTO kaliop_migrations VALUES (?,?,?,?,?,?), (?,?,?,?,?,?), (?,?,?,?,?,?)',
            [
                'a.yml', '/p/a.yml', 'x', 1760000000, 2, null,
                'b.yml', '/p/b.yml', 'x', 1760000100, 3, 'boom',
                'c.yml', '/p/c.yml', 'x', null, 4, '',
            ],
        );
        $this->source = new KaliopMigrationSource($this->connection, 'kaliop_migrations', true);
    }

    public function testListsEntriesWithStatusPathAndError(): void
    {
        $page = $this->source->search(new MigrationCriteria());
        $byName = [];
        foreach ($page->entries as $entry) {
            $byName[$entry->name] = $entry;
        }

        self::assertSame(3, $page->total);
        self::assertSame(MigrationStatus::Done, $byName['a.yml']->status);
        self::assertSame('/p/a.yml', $byName['a.yml']->path);
        self::assertNotNull($byName['a.yml']->executedAt);
        self::assertSame(MigrationStatus::Failed, $byName['b.yml']->status);
        self::assertSame('boom', $byName['b.yml']->error);
        self::assertNull($byName['c.yml']->error, 'empty error is normalised to null');
        self::assertNull($byName['c.yml']->executedAt);
    }

    public function testCountByStatus(): void
    {
        self::assertSame(['done' => 1, 'failed' => 1, 'skipped' => 1], $this->source->countByStatus());
    }

    public function testStatusAndTextFilters(): void
    {
        $failed = $this->source->search(new MigrationCriteria(null, MigrationStatus::Failed));
        self::assertSame(1, $failed->total);
        self::assertSame('b.yml', $failed->entries[0]->name);

        self::assertSame(1, $this->source->search(new MigrationCriteria('/p/a'))->total, 'search also matches the path');
        self::assertSame(0, $this->source->search(new MigrationCriteria('b', MigrationStatus::Done))->total);
    }

    public function testUnavailableWithoutTable(): void
    {
        self::assertFalse((new KaliopMigrationSource($this->connection, 'nope', true))->isAvailable());
    }
}
