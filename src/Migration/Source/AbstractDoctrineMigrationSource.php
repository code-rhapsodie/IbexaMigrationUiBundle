<?php

declare(strict_types=1);

namespace CodeRhapsodie\IbexaMigrationUiBundle\Migration\Source;

use CodeRhapsodie\IbexaMigrationUiBundle\Migration\MigrationCriteria;
use CodeRhapsodie\IbexaMigrationUiBundle\Migration\MigrationEntry;
use CodeRhapsodie\IbexaMigrationUiBundle\Migration\MigrationPage;
use CodeRhapsodie\IbexaMigrationUiBundle\Migration\MigrationStatus;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use ReflectionClass;
use Throwable;

/**
 * Reads the Doctrine Migrations metadata table (version, executed_at, execution_time).
 *
 * The same table is shared by ibexa/doctrine-migrations: a version belongs to Ibexa when its
 * class is one of $ibexaClasses. Concrete sources show either the Ibexa ones or the others.
 */
abstract class AbstractDoctrineMigrationSource extends AbstractTableSource implements PendingMigrationSourceInterface
{
    /**
     * @param list<string>                    $ibexaClasses
     * @param (callable(): list<string>)|null $registeredClasses classes of all the migrations registered in Doctrine Migrations
     */
    public function __construct(
        Connection $connection,
        string $table,
        bool $enabled,
        protected readonly array $ibexaClasses = [],
        private readonly mixed $registeredClasses = null,
    ) {
        parent::__construct($connection, $table, $enabled);
    }

    public function countByStatus(): array
    {
        $counts = [MigrationStatus::Done->value => $this->count($this->baseQuery())];
        $pending = \count($this->getPending());
        if ($pending > 0) {
            $counts[MigrationStatus::Todo->value] = $pending;
        }

        return $counts;
    }

    public function getPending(): array
    {
        if (!\is_callable($this->registeredClasses)) {
            return [];
        }

        try {
            $executed = array_flip(array_map(strval(...), $this->run($this->createQueryBuilder()->select('m.version'))->fetchFirstColumn()));
            $registered = ($this->registeredClasses)();
        } catch (Throwable) {
            return [];
        }

        $pending = [];
        foreach (array_unique($registered) as $class) {
            if (!isset($executed[$class]) && $this->ownsClass($class)) {
                $pending[] = new MigrationEntry(
                    name: $class,
                    status: MigrationStatus::Todo,
                    description: $this->describe($class),
                    targetVersion: $this->targetVersion($class),
                );
            }
        }

        usort($pending, static fn (MigrationEntry $a, MigrationEntry $b): int => strcmp($a->name, $b->name));

        return $pending;
    }

    public function search(MigrationCriteria $criteria): MigrationPage
    {
        // Doctrine only records executed migrations: the others come from the registered classes.
        if ($criteria->status === MigrationStatus::Todo) {
            return MigrationPage::fromList($this->getPending(), $criteria);
        }
        if ($criteria->status instanceof MigrationStatus && $criteria->status !== MigrationStatus::Done) {
            return new MigrationPage([], 0);
        }

        $qb = $this->baseQuery();
        if ($criteria->query !== null && $criteria->query !== '') {
            $qb->andWhere("m.version LIKE :q ESCAPE '!'")->setParameter('q', $this->likeValue($criteria->query));
        }

        $total = $this->count($qb);
        $rows = $this->fetchRows(
            $qb->select('m.version', 'm.executed_at', 'm.execution_time')
                ->orderBy('m.executed_at', 'DESC')
                ->addOrderBy('m.version', 'DESC')
                ->setFirstResult($criteria->offset)
                ->setMaxResults($criteria->limit),
        );

        $entries = array_map(fn (array $r): MigrationEntry => new MigrationEntry(
            name: (string) $r['version'],
            status: MigrationStatus::Done,
            executedAt: $this->toDate($r['executed_at']),
            executionTime: $r['execution_time'] !== null ? (float) $r['execution_time'] : null,
            description: $this->describe((string) $r['version']),
            targetVersion: $this->targetVersion((string) $r['version']),
        ), $rows);

        return new MigrationPage($entries, $total);
    }

    /**
     * Doctrine stores the migration class name; its description comes from getDescription().
     * The class is instantiated without its constructor so no connection or logger is needed.
     */
    private function describe(string $class): ?string
    {
        try {
            if (!class_exists($class) || !method_exists($class, 'getDescription')) {
                return null;
            }

            $reflection = new ReflectionClass($class);
            if ($reflection->isAbstract()) {
                return null;
            }

            $description = $reflection->newInstanceWithoutConstructor()->getDescription();
        } catch (Throwable) {
            return null;
        }

        return \is_string($description) && trim($description) !== '' ? trim($description) : null;
    }

    /** Ibexa migrations (IbexaMigrationInterface) declare the Ibexa version they target. */
    private function targetVersion(string $class): ?string
    {
        try {
            if (!class_exists($class) || !method_exists($class, 'getTargetVersion')) {
                return null;
            }

            $version = $class::getTargetVersion();
        } catch (Throwable) {
            return null;
        }

        return \is_string($version) && $version !== '' ? $version : null;
    }

    /** Whether a registered migration class is the responsibility of this source. */
    abstract protected function ownsClass(string $class): bool;

    /** Restricts the query to the versions this source is responsible for. */
    abstract protected function applyScope(QueryBuilder $qb): void;

    private function baseQuery(): QueryBuilder
    {
        $qb = $this->createQueryBuilder();
        $this->applyScope($qb);

        return $qb;
    }
}
