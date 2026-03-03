<?php

declare(strict_types=1);

namespace App\Tests\Unit\Log\Service;

use App\Log\DTO\LogBatchRequestDTO;
use App\Log\DTO\LogEntryDTO;
use App\Log\Message\ProcessLogMessage;
use App\Log\Service\LogPublisher;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class LogPublisherTest extends TestCase
{
    public function testDispatchesMessageForEachLogEntry(): void
    {
        $dispatched = [];
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->exactly(2))
            ->method('dispatch')
            ->willReturnCallback(function (object $message) use (&$dispatched): Envelope {
                $dispatched[] = $message;

                return new Envelope($message);
            });

        $publisher = new LogPublisher($bus, new NullLogger());

        $batch = new LogBatchRequestDTO(logs: [
            new LogEntryDTO(
                timestamp: '2026-02-26T10:30:45+00:00',
                level: 'error',
                service: 'auth-service',
                message: 'Error 1',
                context: ['key' => 'value'],
                traceId: 'trace-1',
            ),
            new LogEntryDTO(
                timestamp: '2026-02-26T10:30:46+00:00',
                level: 'info',
                service: 'api-gateway',
                message: 'Info 1',
            ),
        ]);

        $publisher->publish($batch, 'batch_abc123');

        $this->assertCount(2, $dispatched);
        $this->assertContainsOnlyInstancesOf(ProcessLogMessage::class, $dispatched);

        $this->assertEquals('batch_abc123', $dispatched[0]->batchId);
        $this->assertEquals('error', $dispatched[0]->level);
        $this->assertEquals('auth-service', $dispatched[0]->service);
        $this->assertEquals('Error 1', $dispatched[0]->message);
        $this->assertEquals(['key' => 'value'], $dispatched[0]->context);
        $this->assertEquals('trace-1', $dispatched[0]->traceId);
        $this->assertEquals(0, $dispatched[0]->retryCount);

        $this->assertEquals('batch_abc123', $dispatched[1]->batchId);
        $this->assertEquals('info', $dispatched[1]->level);
        $this->assertNull($dispatched[1]->context);
        $this->assertNull($dispatched[1]->traceId);
    }

    public function testAllMessagesShareSameBatchIdAndPublishedAt(): void
    {
        $dispatched = [];
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->exactly(5))
            ->method('dispatch')
            ->willReturnCallback(function (object $message) use (&$dispatched): Envelope {
                $dispatched[] = $message;

                return new Envelope($message);
            });

        $publisher = new LogPublisher($bus, new NullLogger());

        $logs = [];
        for ($i = 0; $i < 5; ++$i) {
            $logs[] = new LogEntryDTO(
                timestamp: '2026-02-26T10:30:45+00:00',
                level: 'info',
                service: 'test',
                message: "msg $i",
            );
        }

        $publisher->publish(new LogBatchRequestDTO(logs: $logs), 'batch_xyz');

        $publishedAts = array_map(fn (ProcessLogMessage $m): string => $m->publishedAt, $dispatched);
        $batchIds = array_map(fn (ProcessLogMessage $m): string => $m->batchId, $dispatched);

        $this->assertCount(1, array_unique($publishedAts));
        $this->assertCount(1, array_unique($batchIds));
        $this->assertEquals('batch_xyz', $batchIds[0]);
    }
}
