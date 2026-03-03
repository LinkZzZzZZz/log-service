<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Contracts;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolationListInterface;

interface ResponseFactoryInterface
{
    /** @param array<string, mixed> $data */
    public function success(object|array $data = [], ?string $message = null, int $code = Response::HTTP_OK): JsonResponse;

    /** @param array<string, mixed> $data */
    public function accepted(object|array $data = [], ?string $message = null): JsonResponse;

    /** @param array<int, array<string, string>> $errors */
    public function fail(string $message, array $errors = [], int $code = Response::HTTP_BAD_REQUEST): JsonResponse;

    public function validationFailed(ConstraintViolationListInterface $violations): JsonResponse;
}
