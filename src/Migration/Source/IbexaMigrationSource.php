<?php

declare(strict_types=1);

namespace CodeRhapsodie\IbexaMigrationUiBundle\Migration\Source;

use CodeRhapsodie\IbexaMigrationUiBundle\Migration\MigrationCriteria;
use CodeRhapsodie\IbexaMigrationUiBundle\Migration\MigrationEntry;
use CodeRhapsodie\IbexaMigrationUiBundle\Migration\MigrationPage;
use CodeRhapsodie\IbexaMigrationUiBundle\Migration\MigrationStatus;

/** Reads the "ibexa_migrations" table of ibexa/migrations (name, executed_at, execution_time). */
final class IbexaMigrationSource extends AbstractTableSource
{
    public function getIdentifier(): string
    {
        return 'ibexa_migrations';
    }

    public function getLabel(): string
    {
        return 'source.ibexa_migrations';
    }

    public function countByStatus(): array
    {
        return [MigrationStatus::Done->value => $this->count($this->createQueryBuilder())];
    }

    public function search(MigrationCriteria $criteria): MigrationPage
    {
        // Only executed migrations are recorded.
        if ($criteria->status instanceof MigrationStatus && $criteria->status !== MigrationStatus::Done) {
            return new MigrationPage([], 0);
        }

        $qb = $this->createQueryBuilder();
        if ($criteria->query !== null && $criteria->query !== '') {
            $qb->andWhere("m.name LIKE :q ESCAPE '!'")->setParameter('q', $this->likeValue($criteria->query));
        }

        $total = $this->count($qb);
        $rows = $this->fetchRows(
            $qb->select('m.name', 'm.executed_at', 'm.execution_time')
                ->orderBy('m.executed_at', 'DESC')
                ->addOrderBy('m.name', 'DESC')
                ->setFirstResult($criteria->offset)
                ->setMaxResults($criteria->limit),
        );

        $entries = array_map(fn (array $r): MigrationEntry => new MigrationEntry(
            name: (string) $r['name'],
            status: MigrationStatus::Done,
            executedAt: $this->toDate($r['executed_at']),
            executionTime: $r['execution_time'] !== null ? (float) $r['execution_time'] : null,
        ), $rows);

        return new MigrationPage($entries, $total);
    }
}
