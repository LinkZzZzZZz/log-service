<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Exception;

use Override;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolationListInterface;

final class ValidationException extends ApiException
{
    public function __construct(
        private readonly ConstraintViolationListInterface $violations,
    ) {
        parent::__construct('Validation failed.');
    }

    #[Override]
    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    public function getViolations(): ConstraintViolationListInterface
    {
        return $this->violations;
    }
}
