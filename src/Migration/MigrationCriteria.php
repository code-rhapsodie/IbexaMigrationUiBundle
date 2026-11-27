<?php

declare(strict_types=1);

namespace CodeRhapsodie\IbexaMigrationUiBundle\Migration;

final class MigrationCriteria
{
    public function __construct(
        public readonly ?string $query = null,
        public readonly ?MigrationStatus $status = null,
        public readonly int $offset = 0,
        public readonly int $limit = 25,
    ) {
    }
}
