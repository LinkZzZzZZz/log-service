<?php

declare(strict_types=1);

namespace App\Tests\Unit\Log\DTO;

use App\Log\DTO\LogEntryDTO;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class LogEntryDTOTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testValidLogEntryPassesValidation(): void
    {
        $entry = new LogEntryDTO(
            timestamp: '2026-02-26T10:30:45+00:00',
            level: 'error',
            service: 'auth-service',
            message: 'User authentication failed',
            context: ['user_id' => 123],
            traceId: 'abc123',
        );

        $violations = $this->validator->validate($entry);

        $this->assertCount(0, $violations);
    }

    public function testMissingTimestampFails(): void
    {
        $entry = new LogEntryDTO(
            level: 'error',
            service: 'auth-service',
            message: 'Some message',
        );

        $violations = $this->validator->validate($entry);

        $this->assertGreaterThan(0, $violations->count());
        $this->assertStringContainsString('timestamp', $violations->get(0)->getPropertyPath());
    }

    public function testMissingLevelFails(): void
    {
        $entry = new LogEntryDTO(
            timestamp: '2026-02-26T10:30:45+00:00',
            service: 'auth-service',
            message: 'Some message',
        );

        $violations = $this->validator->validate($entry);

        $this->assertGreaterThan(0, $violations->count());
    }

    public function testInvalidLevelFails(): void
    {
        $entry = new LogEntryDTO(
            timestamp: '2026-02-26T10:30:45+00:00',
            level: 'invalid_level',
            service: 'auth-service',
            message: 'Some message',
        );

        $violations = $this->validator->validate($entry);

        $this->assertGreaterThan(0, $violations->count());
        $this->assertStringContainsString('level', $violations->get(0)->getMessage());
    }

    public function testMissingServiceFails(): void
    {
        $entry = new LogEntryDTO(
            timestamp: '2026-02-26T10:30:45+00:00',
            level: 'info',
            message: 'Some message',
        );

        $violations = $this->validator->validate($entry);

        $this->assertGreaterThan(0, $violations->count());
    }

    public function testMissingMessageFails(): void
    {
        $entry = new LogEntryDTO(
            timestamp: '2026-02-26T10:30:45+00:00',
            level: 'info',
            service: 'auth-service',
        );

        $violations = $this->validator->validate($entry);

        $this->assertGreaterThan(0, $violations->count());
    }

    public function testInvalidTimestampFormatFails(): void
    {
        $entry = new LogEntryDTO(
            timestamp: 'not-a-date',
            level: 'info',
            service: 'auth-service',
            message: 'Some message',
        );

        $violations = $this->validator->validate($entry);

        $this->assertGreaterThan(0, $violations->count());
    }

    public function testOptionalFieldsCanBeNull(): void
    {
        $entry = new LogEntryDTO(
            timestamp: '2026-02-26T10:30:45+00:00',
            level: 'info',
            service: 'auth-service',
            message: 'Some message',
        );

        $violations = $this->validator->validate($entry);

        $this->assertCount(0, $violations);
    }

    #[DataProvider('allValidLevelsProvider')]
    public function testAllValidLogLevels(string $level): void
    {
        $entry = new LogEntryDTO(
            timestamp: '2026-02-26T10:30:45+00:00',
            level: $level,
            service: 'test-service',
            message: 'Test message',
        );

        $violations = $this->validator->validate($entry);

        $this->assertCount(0, $violations);
    }

    public function testServiceNameTooLongFails(): void
    {
        $entry = new LogEntryDTO(
            timestamp: '2026-02-26T10:30:45+00:00',
            level: 'error',
            service: str_repeat('x', 256),
            message: 'Some message',
        );

        $violations = $this->validator->validate($entry);

        $this->assertGreaterThan(0, $violations->count());
    }

    public function testMessageTooLongFails(): void
    {
        $entry = new LogEntryDTO(
            timestamp: '2026-02-26T10:30:45+00:00',
            level: 'error',
            service: 'auth-service',
            message: str_repeat('x', 8193),
        );

        $violations = $this->validator->validate($entry);

        $this->assertGreaterThan(0, $violations->count());
    }

    public function testTraceIdTooLongFails(): void
    {
        $entry = new LogEntryDTO(
            timestamp: '2026-02-26T10:30:45+00:00',
            level: 'error',
            service: 'auth-service',
            message: 'Some message',
            traceId: str_repeat('x', 257),
        );

        $violations = $this->validator->validate($entry);

        $this->assertGreaterThan(0, $violations->count());
    }

    public function testContextWithTooManyKeysFails(): void
    {
        $context = [];
        for ($i = 0; $i < 65; ++$i) {
            $context["key_$i"] = "value_$i";
        }

        $entry = new LogEntryDTO(
            timestamp: '2026-02-26T10:30:45+00:00',
            level: 'error',
            service: 'auth-service',
            message: 'Some message',
            context: $context,
        );

        $violations = $this->validator->validate($entry);

        $this->assertGreaterThan(0, $violations->count());
    }

    public function testMaxLengthFieldsPass(): void
    {
        $entry = new LogEntryDTO(
            timestamp: '2026-02-26T10:30:45+00:00',
            level: 'error',
            service: str_repeat('x', 255),
            message: str_repeat('x', 8192),
            traceId: str_repeat('x', 256),
        );

        $violations = $this->validator->validate($entry);

        $this->assertCount(0, $violations);
    }

    public static function allValidLevelsProvider(): Generator
    {
        $levels = ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'];

        foreach ($levels as $level) {
            yield $level => [$level];
        }
    }
}
