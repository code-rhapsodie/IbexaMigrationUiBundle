<?php

declare(strict_types=1);

namespace CodeRhapsodie\IbexaMigrationUiBundle\Migration\Source;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Result;
use Exception;
use Throwable;

abstract class AbstractTableSource implements MigrationSourceInterface
{
    private ?bool $tableExists = null;

    public function __construct(
        protected readonly Connection $connection,
        protected readonly string $table,
        protected readonly bool $enabled = true,
    ) {
    }

    public function isAvailable(): bool
    {
        if (!$this->enabled) {
            return false;
        }

        if ($this->tableExists === null) {
            try {
                // Version-agnostic probe (DBAL 2.13 to 4): works without the schema manager API.
                $this->connection->executeQuery('SELECT 1 FROM ' . $this->connection->quoteIdentifier($this->table) . ' WHERE 1 = 0');
                $this->tableExists = true;
            } catch (Throwable) {
                $this->tableExists = false;
            }
        }

        return $this->tableExists;
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        return $this->connection->createQueryBuilder()->from($this->connection->quoteIdentifier($this->table), 'm');
    }

    protected function count(QueryBuilder $qb): int
    {
        return (int) $this->run((clone $qb)->select('COUNT(*)'))->fetchOne();
    }

    /**
     * Runs a query builder on any supported DBAL version: QueryBuilder::executeQuery() does not
     * exist in DBAL 2.13 (Ibexa 4.6) and QueryBuilder::execute() is gone in DBAL 4.
     */
    protected function run(QueryBuilder $qb): Result
    {
        return $this->connection->executeQuery($qb->getSQL(), $qb->getParameters(), $qb->getParameterTypes());
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function fetchRows(QueryBuilder $qb): array
    {
        return $this->run($qb)->fetchAllAssociative();
    }

    /**
     * Binds one placeholder per value instead of an array parameter, whose type constant differs
     * between DBAL 2.13 (Connection::PARAM_*_ARRAY) and DBAL 3.6+ (ArrayParameterType).
     *
     * @param list<int|string> $values
     */
    protected function whereIn(QueryBuilder $qb, string $column, array $values, string $name, bool $negate = false): void
    {
        $placeholders = [];
        foreach ($values as $i => $value) {
            $placeholders[] = ':' . $name . $i;
            $qb->setParameter($name . $i, $value, \is_int($value) ? ParameterType::INTEGER : ParameterType::STRING);
        }

        $qb->andWhere($column . ($negate ? ' NOT IN (' : ' IN (') . implode(', ', $placeholders) . ')');
    }

    protected function likeValue(string $query): string
    {
        return '%' . preg_replace('/[%_!]/', '!$0', $query) . '%';
    }

    protected function toDate(mixed $value): ?DateTimeImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return is_numeric($value)
                ? (new DateTimeImmutable())->setTimestamp((int) $value)
                : new DateTimeImmutable((string) $value);
        } catch (Exception) {
            return null;
        }
    }
}
