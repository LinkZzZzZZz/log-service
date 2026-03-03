<?php

declare(strict_types=1);

namespace App\Log\DTO;

final readonly class IngestLogsResultDTO
{
    public function __construct(
        public string $batchId,
        public int $logsCount,
    ) {
    }
}
