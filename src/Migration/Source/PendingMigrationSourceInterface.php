<?php

declare(strict_types=1);

namespace CodeRhapsodie\IbexaMigrationUiBundle\Migration\Source;

use CodeRhapsodie\IbexaMigrationUiBundle\Migration\MigrationEntry;

/** A source that can also list the migrations it knows about but that were never executed. */
interface PendingMigrationSourceInterface extends MigrationSourceInterface
{
    /** @return list<MigrationEntry> migrations with the "todo" status, in execution order */
    public function getPending(): array;
}
