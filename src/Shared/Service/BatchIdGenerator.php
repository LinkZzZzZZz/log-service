<?php

declare(strict_types=1);

namespace App\Shared\Service;

use Symfony\Component\Uid\Uuid;

final class BatchIdGenerator
{
    public function generate(): string
    {
        return 'batch_'.str_replace('-', '', Uuid::v4()->toRfc4122());
    }
}
