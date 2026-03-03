<?php

declare(strict_types=1);

namespace App\Log\Handler;

use App\Log\DTO\IngestLogsResultDTO;
use App\Log\DTO\LogBatchRequestDTO;
use App\Log\Service\LogPublisher;
use App\Shared\Service\BatchIdGenerator;
use Psr\Log\LoggerInterface;

final readonly class IngestLogsHandler
{
    public function __construct(
        private LogPublisher $logPublisher,
        private BatchIdGenerator $batchIdGenerator,
        private LoggerInterface $logger,
    ) {
    }

    public function handle(LogBatchRequestDTO $batch): IngestLogsResultDTO
    {
        $batchId = $this->batchIdGenerator->generate();
        $logsCount = count($batch->logs);

        $this->logger->info('Processing log batch', [
            'batchId' => $batchId,
            'logsCount' => $logsCount,
        ]);

        $this->logPublisher->publish($batch, $batchId);

        return new IngestLogsResultDTO($batchId, $logsCount);
    }
}
