<?php

declare(strict_types=1);

namespace CodeRhapsodie\IbexaMigrationUiBundle\Tests\Migration;

use CodeRhapsodie\IbexaMigrationUiBundle\Migration\MigrationStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MigrationStatusTest extends TestCase
{
    #[DataProvider('kaliopCodes')]
    public function testFromKaliop(int $code, MigrationStatus $expected): void
    {
        self::assertSame($expected, MigrationStatus::fromKaliop($code));
    }

    /**
     * @return iterable<int, array{int, MigrationStatus}>
     */
    public static function kaliopCodes(): iterable
    {
        yield [0, MigrationStatus::Todo];
        yield [1, MigrationStatus::Started];
        yield [2, MigrationStatus::Done];
        yield [3, MigrationStatus::Failed];
        yield [4, MigrationStatus::Skipped];
        yield [5, MigrationStatus::PartiallyDone];
        yield [6, MigrationStatus::Suspended];
        yield [99, MigrationStatus::Todo];
    }

    public function testTranslationKey(): void
    {
        self::assertSame('status.partially_done', MigrationStatus::PartiallyDone->translationKey());
    }
}
