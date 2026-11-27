<?php

declare(strict_types=1);

namespace CodeRhapsodie\IbexaMigrationUiBundle\Migration\Source;

use Doctrine\DBAL\Query\QueryBuilder;

/** Application migrations: every version of the Doctrine table that does not belong to Ibexa. */
final class DoctrineMigrationSource extends AbstractDoctrineMigrationSource
{
    public function getIdentifier(): string
    {
        return 'doctrine';
    }

    public function getLabel(): string
    {
        return 'source.doctrine';
    }

    protected function ownsClass(string $class): bool
    {
        return !\in_array($class, $this->ibexaClasses, true);
    }

    protected function applyScope(QueryBuilder $qb): void
    {
        if ($this->ibexaClasses !== []) {
            $this->whereIn($qb, 'm.version', $this->ibexaClasses, 'ibexa', true);
        }
    }
}
