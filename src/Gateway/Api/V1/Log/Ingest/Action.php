<?php

declare(strict_types=1);

namespace App\Gateway\Api\V1\Log\Ingest;

use App\Infrastructure\Http\BaseController;
use App\Infrastructure\Http\Contracts\ResponseFactoryInterface;
use App\Log\DTO\LogBatchRequestDTO;
use App\Log\DTO\LogBatchResponseDTO;
use App\Log\Handler\IngestLogsHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class Action extends BaseController
{
    public function __construct(
        ResponseFactoryInterface $responseFactory,
        private readonly IngestLogsHandler $handler,
    ) {
        parent::__construct($responseFactory);
    }

    #[Route(path: '/logs/ingest', name: 'api_v1_logs_ingest', methods: ['POST'])]
    public function __invoke(
        #[MapRequestPayload]
        LogBatchRequestDTO $batch,
    ): JsonResponse {
        $result = $this->handler->handle($batch);

        return $this->responseFactory->accepted(
            new LogBatchResponseDTO(
                batchId: $result->batchId,
                logsCount: $result->logsCount,
            ),
        );
    }
}
