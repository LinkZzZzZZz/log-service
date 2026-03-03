<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Factory;

use App\Infrastructure\Http\Contracts\ResponseFactoryInterface;
use Override;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

final readonly class ResponseFactory implements ResponseFactoryInterface
{
    public function __construct(
        private SerializerInterface $serializer,
    ) {
    }

    /** @param array<string, mixed> $data */
    #[Override]
    public function success(object|array $data = [], ?string $message = null, int $code = Response::HTTP_OK): JsonResponse
    {
        return $this->json([
            'success' => true,
            'data' => $this->normalize($data),
            'message' => $message,
        ], $code);
    }

    /** @param array<string, mixed> $data */
    #[Override]
    public function accepted(object|array $data = [], ?string $message = null): JsonResponse
    {
        return $this->json([
            'success' => true,
            'data' => $this->normalize($data),
            'message' => $message,
        ], Response::HTTP_ACCEPTED);
    }

    /** @param array<int, array<string, string>> $errors */
    #[Override]
    public function fail(string $message, array $errors = [], int $code = Response::HTTP_BAD_REQUEST): JsonResponse
    {
        return $this->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }

    #[Override]
    public function validationFailed(ConstraintViolationListInterface $violations): JsonResponse
    {
        $errors = [];
        foreach ($violations as $violation) {
            $errors[] = [
                'field' => $violation->getPropertyPath(),
                'message' => (string) $violation->getMessage(),
            ];
        }

        return $this->json([
            'success' => false,
            'message' => 'Validation failed.',
            'errors' => $errors,
        ], Response::HTTP_BAD_REQUEST);
    }

    /** @param array<string, mixed> $data
     * @return array<string, mixed> */
    private function normalize(object|array $data): array
    {
        if (is_array($data)) {
            return $data;
        }

        $json = $this->serializer->serialize($data, 'json');

        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }

    /** @param array<string, mixed> $payload */
    private function json(array $payload, int $code): JsonResponse
    {
        return new JsonResponse($payload, $code);
    }
}
