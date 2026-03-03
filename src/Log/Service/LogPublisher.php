<?php

declare(strict_types=1);

namespace App\Log\Service;

use App\Log\DTO\LogBatchRequestDTO;
use App\Log\Message\ProcessLogMessage;
use DateTimeImmutable;
use DateTimeInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class LogPublisher
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private LoggerInterface $logger,
    ) {
    }

    public function publish(LogBatchRequestDTO $batch, string $batchId): void
    {
        $publishedAt = new DateTimeImmutable()->format(DateTimeInterface::ATOM);

        foreach ($batch->logs as $logEntry) {
            $message = new ProcessLogMessage(
                batchId: $batchId,
                timestamp: $logEntry->timestamp,
                level: $logEntry->level,
                service: $logEntry->service,
                message: $logEntry->message,
                context: $logEntry->context,
                traceId: $logEntry->traceId,
                publishedAt: $publishedAt,
            );

            $this->messageBus->dispatch($message);
        }

        $this->logger->info('Published batch :batchId to queue', [
            'batchId' => $batchId,
            'messagesCount' => count($batch->logs),
        ]);
    }
}
