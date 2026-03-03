<?php

declare(strict_types=1);

namespace App\Tests\Unit\Log\DTO;

use App\Log\DTO\LogBatchRequestDTO;
use App\Log\DTO\LogEntryDTO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class LogBatchRequestDTOTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testValidBatchPassesValidation(): void
    {
        $batch = new LogBatchRequestDTO(logs: [
            new LogEntryDTO(
                timestamp: '2026-02-26T10:30:45+00:00',
                level: 'error',
                service: 'auth-service',
                message: 'Error occurred',
            ),
            new LogEntryDTO(
                timestamp: '2026-02-26T10:30:46+00:00',
                level: 'info',
                service: 'api-gateway',
                message: 'Request processed',
            ),
        ]);

        $violations = $this->validator->validate($batch);

        $this->assertCount(0, $violations);
    }

    public function testEmptyBatchFails(): void
    {
        $batch = new LogBatchRequestDTO(logs: []);

        $violations = $this->validator->validate($batch);

        $this->assertGreaterThan(0, $violations->count());
    }

    public function testBatchExceeding1000LogsFails(): void
    {
        $logs = [];
        for ($i = 0; $i < 1001; ++$i) {
            $logs[] = new LogEntryDTO(
                timestamp: '2026-02-26T10:30:45+00:00',
                level: 'info',
                service: 'test-service',
                message: "Log entry $i",
            );
        }

        $batch = new LogBatchRequestDTO(logs: $logs);

        $violations = $this->validator->validate($batch);

        $this->assertGreaterThan(0, $violations->count());
    }

    public function testBatchWithInvalidEntryFails(): void
    {
        $batch = new LogBatchRequestDTO(logs: [
            new LogEntryDTO(
                timestamp: '2026-02-26T10:30:45+00:00',
                level: 'error',
                service: 'auth-service',
                message: 'Valid entry',
            ),
            new LogEntryDTO(
                level: 'error',
                service: 'auth-service',
                message: 'Missing timestamp',
            ),
        ]);

        $violations = $this->validator->validate($batch);

        $this->assertGreaterThan(0, $violations->count());
    }
}
