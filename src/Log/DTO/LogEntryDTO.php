<?php

declare(strict_types=1);

namespace App\Log\DTO;

use DateTimeInterface;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class LogEntryDTO
{
    /** @param array<string, mixed>|null $context */
    public function __construct(
        #[Assert\NotBlank(message: 'Field "timestamp" is required.')]
        #[Assert\DateTime(format: DateTimeInterface::ATOM, message: 'Field "timestamp" must be a valid ISO 8601 date.')]
        public string $timestamp = '',
        #[Assert\NotBlank(message: 'Field "level" is required.')]
        #[Assert\Choice(
            choices: ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'],
            message: 'Field "level" must be one of: emergency, alert, critical, error, warning, notice, info, debug.',
        )]
        public string $level = '',
        #[Assert\NotBlank(message: 'Field "service" is required.')]
        #[Assert\Length(max: 255, maxMessage: 'Field "service" must be at most {{ limit }} characters.')]
        public string $service = '',
        #[Assert\NotBlank(message: 'Field "message" is required.')]
        #[Assert\Length(max: 8192, maxMessage: 'Field "message" must be at most {{ limit }} characters.')]
        public string $message = '',
        #[Assert\Count(max: 64, maxMessage: 'Field "context" must have at most {{ limit }} keys.')]
        public ?array $context = null,
        #[SerializedName('trace_id')]
        #[Assert\Length(max: 256, maxMessage: 'Field "trace_id" must be at most {{ limit }} characters.')]
        public ?string $traceId = null,
    ) {
    }
}
