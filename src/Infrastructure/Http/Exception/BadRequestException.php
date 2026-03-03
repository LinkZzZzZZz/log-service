<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Exception;

use Override;
use Symfony\Component\HttpFoundation\Response;

final class BadRequestException extends ApiException
{
    #[Override]
    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}
