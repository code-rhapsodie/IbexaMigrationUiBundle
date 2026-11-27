<?php

declare(strict_types=1);

namespace CodeRhapsodie\IbexaMigrationUiBundle\Migration;

enum MigrationStatus: string
{
    case Todo = 'todo';
    case Started = 'started';
    case Done = 'done';
    case Failed = 'failed';
    case Skipped = 'skipped';
    case PartiallyDone = 'partially_done';
    case Suspended = 'suspended';

    /** Kaliop status codes (Kaliop\eZMigrationBundle\API\Value\Migration::STATUS_*). */
    public static function fromKaliop(int $code): self
    {
        return match ($code) {
            1 => self::Started,
            2 => self::Done,
            3 => self::Failed,
            4 => self::Skipped,
            5 => self::PartiallyDone,
            6 => self::Suspended,
            default => self::Todo,
        };
    }

    public function translationKey(): string
    {
        return 'status.' . $this->value;
    }
}
