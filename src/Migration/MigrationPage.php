<?php

declare(strict_types=1);

namespace CodeRhapsodie\IbexaMigrationUiBundle\Migration;

final class MigrationPage
{
    /** @param list<MigrationEntry> $entries */
    public function __construct(
        public readonly array $entries,
        public readonly int $total,
    ) {
    }

    /**
     * Searches (name, path, description) and paginates an in-memory list.
     *
     * @param list<MigrationEntry> $entries
     */
    public static function fromList(array $entries, MigrationCriteria $criteria): self
    {
        if ($criteria->query !== null && $criteria->query !== '') {
            $entries = array_values(array_filter($entries, static function (MigrationEntry $entry) use ($criteria): bool {
                foreach ([$entry->name, $entry->path, $entry->description] as $haystack) {
                    if ($haystack !== null && stripos($haystack, (string) $criteria->query) !== false) {
                        return true;
                    }
                }

                return false;
            }));
        }

        return new self(\array_slice($entries, $criteria->offset, $criteria->limit), \count($entries));
    }
}
