<?php

declare(strict_types=1);

namespace App\Log\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class LogBatchRequestDTO
{
    /**
     * @param LogEntryDTO[] $logs
     */
    public function __construct(
        #[Assert\NotBlank(message: 'Field "logs" is required and must not be empty.')]
        #[Assert\Count(
            max: 1000,
            maxMessage: 'A batch cannot contain more than {{ limit }} log entries.',
        )]
        #[Assert\Valid]
        public array $logs = [],
    ) {
    }
}
