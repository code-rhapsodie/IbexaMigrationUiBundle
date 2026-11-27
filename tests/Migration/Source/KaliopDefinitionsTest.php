<?php

declare(strict_types=1);

namespace CodeRhapsodie\IbexaMigrationUiBundle\Tests\Migration\Source;

use CodeRhapsodie\IbexaMigrationUiBundle\Migration\Source\Provider\KaliopDefinitions;
use PHPUnit\Framework\TestCase;

final class KaliopDefinitionsTest extends TestCase
{
    public function testIgnoresFilesThatAreNotMigrations(): void
    {
        $loader = new class {
            /**
             * @param list<string> $paths
             *
             * @return array<string, string>
             */
            public function listAvailableDefinitions(array $paths): array
            {
                return ['.gitkeep' => '/m/.gitkeep', 'README.md' => '/m/README.md', 'a.yml' => '/m/a.yml', 'b.SQL' => '/m/b.SQL'];
            }
        };

        $definitions = (new KaliopDefinitions($loader, null, '/p'))();

        self::assertSame(['a.yml', 'b.SQL'], array_keys($definitions));
    }
}
