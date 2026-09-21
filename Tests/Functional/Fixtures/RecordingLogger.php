<?php

declare(strict_types=1);

/*
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) Leuchtfeuer Digital Marketing <dev@Leuchtfeuer.com>
 */

namespace Leuchtfeuer\Auth0\Tests\Functional\Fixtures;

use Psr\Log\AbstractLogger;

/**
 * Keeps what was logged, so a test can assert on the severity a failure was
 * reported with and surface the underlying exception in its own failure
 * message instead of leaving it swallowed.
 */
final class RecordingLogger extends AbstractLogger
{
    /** @var list<array{level: string, message: string, context: array<string, mixed>}> */
    private array $records = [];

    /**
     * @param array<string, mixed> $context
     */
    public function log($level, \Stringable|string $message, array $context = []): void
    {
        $this->records[] = [
            'level' => (string)$level,
            'message' => (string)$message,
            'context' => $context,
        ];
    }

    public function getLoggedException(): ?\Throwable
    {
        foreach ($this->records as $record) {
            if (($record['context']['exception'] ?? null) instanceof \Throwable) {
                return $record['context']['exception'];
            }
        }

        return null;
    }

    public function getLastLevel(): ?string
    {
        $last = end($this->records);

        return $last === false ? null : $last['level'];
    }

    /**
     * @return list<array{level: string, message: string, context: array<string, mixed>}>
     */
    public function getRecords(): array
    {
        return $this->records;
    }
}
