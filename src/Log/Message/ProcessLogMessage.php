<?php

declare(strict_types=1);

namespace App\Log\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage(transport: 'logs_ingest')]
final class ProcessLogMessage
{
    public const int VERSION = 1;

    /** @param array<string, mixed>|null $context */
    public function __construct(
        public string $batchId,
        public string $timestamp,
        public string $level,
        public string $service,
        public string $message,
        public ?array $context,
        public ?string $traceId,
        public string $publishedAt,
        public int $retryCount = 0,
        public int $version = self::VERSION,
    ) {
    }
}
