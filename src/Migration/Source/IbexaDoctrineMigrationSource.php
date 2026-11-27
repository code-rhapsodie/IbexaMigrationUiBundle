<?php

declare(strict_types=1);

namespace CodeRhapsodie\IbexaMigrationUiBundle\Migration\Source;

use Doctrine\DBAL\Query\QueryBuilder;

/** Migrations registered by ibexa/doctrine-migrations (Ibexa DXP schema migrations). */
final class IbexaDoctrineMigrationSource extends AbstractDoctrineMigrationSource
{
    public function getIdentifier(): string
    {
        return 'ibexa_doctrine';
    }

    public function getLabel(): string
    {
        return 'source.ibexa_doctrine';
    }

    public function isAvailable(): bool
    {
        return $this->ibexaClasses !== [] && parent::isAvailable();
    }

    protected function ownsClass(string $class): bool
    {
        return \in_array($class, $this->ibexaClasses, true);
    }

    protected function applyScope(QueryBuilder $qb): void
    {
        $this->whereIn($qb, 'm.version', $this->ibexaClasses, 'ibexa');
    }
}
