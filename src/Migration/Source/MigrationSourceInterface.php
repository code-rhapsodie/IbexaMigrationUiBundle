<?php

declare(strict_types=1);

namespace CodeRhapsodie\IbexaMigrationUiBundle\Migration\Source;

use CodeRhapsodie\IbexaMigrationUiBundle\Migration\MigrationCriteria;
use CodeRhapsodie\IbexaMigrationUiBundle\Migration\MigrationPage;

interface MigrationSourceInterface
{
    /** Stable identifier used in the URL (?source=...). */
    public function getIdentifier(): string;

    /** Translation key (domain "ibexa_migration_ui"). */
    public function getLabel(): string;

    /** False when disabled, library not installed, or table missing. */
    public function isAvailable(): bool;

    /** @return array<string, int> MigrationStatus value => number of migrations */
    public function countByStatus(): array;

    public function search(MigrationCriteria $criteria): MigrationPage;
}
