<?php

declare(strict_types=1);

namespace CodeRhapsodie\IbexaMigrationUiBundle\Migration\Source;

use CodeRhapsodie\IbexaMigrationUiBundle\Migration\MigrationCriteria;
use CodeRhapsodie\IbexaMigrationUiBundle\Migration\MigrationEntry;
use CodeRhapsodie\IbexaMigrationUiBundle\Migration\MigrationPage;
use CodeRhapsodie\IbexaMigrationUiBundle\Migration\MigrationStatus;
use Doctrine\DBAL\Connection;
use Throwable;

/** Reads the Kaliop migration table (migration, path, md5, execution_date, status, execution_error). */
final class KaliopMigrationSource extends AbstractTableSource implements PendingMigrationSourceInterface
{
    /**
     * @param (callable(): array<string, string>)|null $definitions name => path of every migration file found on disk
     */
    public function __construct(
        Connection $connection,
        string $table,
        bool $enabled = true,
        private readonly mixed $definitions = null,
    ) {
        parent::__construct($connection, $table, $enabled);
    }

    public function getIdentifier(): string
    {
        return 'kaliop';
    }

    public function getLabel(): string
    {
        return 'source.kaliop';
    }

    public function countByStatus(): array
    {
        $rows = $this->fetchRows(
            $this->createQueryBuilder()
                ->select('m.status', 'COUNT(*) AS total')
                ->groupBy('m.status'),
        );

        $counts = [];
        foreach ($rows as $row) {
            $key = MigrationStatus::fromKaliop((int) $row['status'])->value;
            $counts[$key] = ($counts[$key] ?? 0) + (int) $row['total'];
        }

        $pending = \count($this->getPending());
        if ($pending > 0) {
            $counts[MigrationStatus::Todo->value] = $pending;
        }

        return $counts;
    }

    /** Migration files found on disk that have no row in the table yet. */
    public function getPending(): array
    {
        if (!\is_callable($this->definitions)) {
            return [];
        }

        try {
            $known = array_flip(array_map(strval(...), $this->run($this->createQueryBuilder()->select('m.migration'))->fetchFirstColumn()));
            $definitions = ($this->definitions)();
        } catch (Throwable) {
            return [];
        }

        $pending = [];
        foreach ($definitions as $name => $path) {
            if (!isset($known[(string) $name])) {
                $pending[] = new MigrationEntry(name: (string) $name, status: MigrationStatus::Todo, path: (string) $path);
            }
        }

        usort($pending, static fn (MigrationEntry $a, MigrationEntry $b): int => strcmp($a->name, $b->name));

        return $pending;
    }

    public function search(MigrationCriteria $criteria): MigrationPage
    {
        if ($criteria->status === MigrationStatus::Todo) {
            return MigrationPage::fromList($this->getPending(), $criteria);
        }

        $qb = $this->createQueryBuilder();
        if ($criteria->query !== null && $criteria->query !== '') {
            $qb->andWhere("m.migration LIKE :q ESCAPE '!' OR m.path LIKE :q ESCAPE '!'")->setParameter('q', $this->likeValue($criteria->query));
        }
        if ($criteria->status instanceof MigrationStatus) {
            $this->whereIn($qb, 'm.status', $this->kaliopCodes($criteria->status), 'code');
        }

        $total = $this->count($qb);
        $rows = $this->fetchRows(
            $qb->select('m.migration', 'm.path', 'm.execution_date', 'm.status', 'm.execution_error')
                ->orderBy('m.execution_date', 'DESC')
                ->addOrderBy('m.migration', 'DESC')
                ->setFirstResult($criteria->offset)
                ->setMaxResults($criteria->limit),
        );

        $entries = array_map(fn (array $r): MigrationEntry => new MigrationEntry(
            name: (string) $r['migration'],
            status: MigrationStatus::fromKaliop((int) $r['status']),
            executedAt: $this->toDate($r['execution_date']),
            error: $r['execution_error'] !== null && $r['execution_error'] !== '' ? (string) $r['execution_error'] : null,
            path: $r['path'] !== null ? (string) $r['path'] : null,
        ), $rows);

        return new MigrationPage($entries, $total);
    }

    /** @return list<int> */
    private function kaliopCodes(MigrationStatus $status): array
    {
        return match ($status) {
            MigrationStatus::Started => [1],
            MigrationStatus::Done => [2],
            MigrationStatus::Failed => [3],
            MigrationStatus::Skipped => [4],
            MigrationStatus::PartiallyDone => [5],
            MigrationStatus::Suspended => [6],
            // Any unknown code is mapped to Todo by fromKaliop().
            MigrationStatus::Todo => [0],
        };
    }
}
