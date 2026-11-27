<?php

declare(strict_types=1);

namespace CodeRhapsodie\IbexaMigrationUiBundle\Controller;

use CodeRhapsodie\IbexaMigrationUiBundle\Migration\MigrationCriteria;
use CodeRhapsodie\IbexaMigrationUiBundle\Migration\MigrationStatus;
use CodeRhapsodie\IbexaMigrationUiBundle\Migration\Source\MigrationSourceInterface;
use CodeRhapsodie\IbexaMigrationUiBundle\Migration\Source\PendingMigrationSourceInterface;
use Ibexa\Core\MVC\Symfony\Security\Authorization\Attribute;
use Ibexa\User\UserSetting\UserSettingService;
use Pagerfanta\Adapter\FixedAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class MigrationController extends AbstractController
{
    private const DEFAULT_PER_PAGE = 25;

    /** @param iterable<MigrationSourceInterface> $sources */
    public function __construct(
        private readonly iterable $sources,
        private readonly ?UserSettingService $userSettingService = null,
    ) {
    }

    /**
     * Uses the user's "sub-items limit" account setting, falling back to DEFAULT_PER_PAGE.
     */
    private function resolvePerPage(): int
    {
        if ($this->userSettingService instanceof UserSettingService) {
            $value = (int) $this->userSettingService->getUserSetting('subitems_limit')->value;
            if ($value > 0) {
                return $value;
            }
        }

        return self::DEFAULT_PER_PAGE;
    }

    public function listAction(Request $request): Response
    {
        $this->denyAccessUnlessGranted(new Attribute('migration_ui', 'view'));

        $sources = [];
        foreach ($this->sources as $source) {
            if ($source->isAvailable()) {
                $sources[$source->getIdentifier()] = $source;
            }
        }

        $currentId = (string) $request->query->get('source', '');
        $current = $sources[$currentId] ?? (reset($sources) ?: null);

        $query = trim((string) $request->query->get('q', ''));
        // Kaliop stores real statuses; the other sources only know "executed" and, if available, "todo".
        $status = MigrationStatus::tryFrom((string) $request->query->get('status', ''));
        $statuses = MigrationStatus::cases();
        if ($current?->getIdentifier() !== 'kaliop') {
            $statuses = $current instanceof PendingMigrationSourceInterface
                ? [MigrationStatus::Done, MigrationStatus::Todo]
                : [];
            if (!\in_array($status, $statuses, true)) {
                $status = null;
            }
        }
        $perPage = $this->resolvePerPage();
        $page = max(1, $request->query->getInt('page', 1));

        $result = null;
        $pager = null;
        $counts = [];
        if ($current !== null) {
            $counts = $current->countByStatus();
            $result = $current->search(new MigrationCriteria(
                $query !== '' ? $query : null,
                $status,
                ($page - 1) * $perPage,
                $perPage,
            ));
            $pager = new Pagerfanta(new FixedAdapter($result->total, $result->entries));
            $pager->setMaxPerPage($perPage);
            $pager->setCurrentPage(min($page, $pager->getNbPages()));
        }

        return $this->render('@CodeRhapsodieIbexaMigrationUi/admin/list.html.twig', [
            'sources' => $sources,
            'current' => $current,
            'result' => $result,
            'counts' => $counts,
            'statuses' => $statuses,
            'query' => $query,
            'status' => $status,
            'pager' => $pager,
        ]);
    }
}
