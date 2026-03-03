<?php

declare(strict_types=1);

namespace App\Log\DTO;

use Symfony\Component\Serializer\Attribute\SerializedName;

final readonly class LogBatchResponseDTO
{
    public function __construct(
        #[SerializedName('batch_id')]
        public string $batchId,
        #[SerializedName('logs_count')]
        public int $logsCount,
    ) {
    }
}
