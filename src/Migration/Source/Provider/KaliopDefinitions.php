<?php

declare(strict_types=1);

namespace CodeRhapsodie\IbexaMigrationUiBundle\Migration\Source\Provider;

use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;

/**
 * Migration files found on disk, as name => path.
 *
 * Directories come from the siteaccess-aware parameter "code_rhapsodie_migration_ui.kaliop_paths"
 * (relative to the project directory); when empty, Kaliop's own default directories are scanned.
 */
final class KaliopDefinitions
{
    /** Formats Kaliop can execute; anything else (.gitkeep, README, ...) is not a migration. */
    private const EXTENSIONS = ['yml', 'yaml', 'json', 'sql', 'php', 'csv'];

    public function __construct(
        private readonly ?object $loader,
        private readonly ?ConfigResolverInterface $configResolver,
        private readonly string $projectDir,
    ) {
    }

    /** @return array<string, string> */
    public function __invoke(): array
    {
        if ($this->loader === null || !method_exists($this->loader, 'listAvailableDefinitions')) {
            return [];
        }

        $paths = [];
        if ($this->configResolver instanceof ConfigResolverInterface
            && $this->configResolver->hasParameter('kaliop_paths', 'code_rhapsodie_migration_ui')) {
            foreach ((array) $this->configResolver->getParameter('kaliop_paths', 'code_rhapsodie_migration_ui') as $path) {
                $path = (string) $path;
                $paths[] = str_starts_with($path, '/') ? $path : $this->projectDir . '/' . $path;
            }
        }

        /** @var array<string, string> $definitions */
        $definitions = $this->loader->listAvailableDefinitions($paths);

        return array_filter(
            $definitions,
            static fn (string $path, string|int $name): bool => \in_array(strtolower(pathinfo((string) $name, \PATHINFO_EXTENSION)), self::EXTENSIONS, true),
            \ARRAY_FILTER_USE_BOTH,
        );
    }
}
