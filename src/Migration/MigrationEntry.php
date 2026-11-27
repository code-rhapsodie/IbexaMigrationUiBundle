<?php

declare(strict_types=1);

namespace CodeRhapsodie\IbexaMigrationUiBundle\Migration;

use DateTimeImmutable;

final class MigrationEntry
{
    public function __construct(
        public readonly string $name,
        public readonly MigrationStatus $status,
        public readonly ?DateTimeImmutable $executedAt = null,
        public readonly ?float $executionTime = null,
        public readonly ?string $error = null,
        public readonly ?string $path = null,
        public readonly ?string $description = null,
        public readonly ?string $targetVersion = null,
    ) {
    }
}
