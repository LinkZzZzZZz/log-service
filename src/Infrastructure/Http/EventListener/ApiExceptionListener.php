<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\EventListener;

use App\Infrastructure\Http\Contracts\ResponseFactoryInterface;
use App\Infrastructure\Http\Exception\ApiException;
use App\Infrastructure\Http\Exception\ValidationException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\Exception\ValidationFailedException;

#[AsEventListener(event: KernelEvents::EXCEPTION)]
final readonly class ApiExceptionListener
{
    private const string API_PREFIX = '/api';

    public function __construct(
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        if (false === str_starts_with($event->getRequest()->getPathInfo(), self::API_PREFIX)) {
            return;
        }

        $exception = $event->getThrowable();

        if ($exception instanceof BadRequestHttpException) {
            $event->setResponse(
                $this->responseFactory->fail($exception->getMessage()),
            );

            return;
        }

        if ($exception instanceof HttpException && $exception->getPrevious() instanceof ValidationFailedException) {
            /** @var ValidationFailedException $validationException */
            $validationException = $exception->getPrevious();
            $event->setResponse(
                $this->responseFactory->validationFailed($validationException->getViolations()),
            );

            return;
        }

        if ($exception instanceof ValidationException) {
            $event->setResponse(
                $this->responseFactory->validationFailed($exception->getViolations()),
            );

            return;
        }

        if ($exception instanceof ApiException) {
            $event->setResponse(
                $this->responseFactory->fail($exception->getMessage(), [], $exception->getStatusCode()),
            );

            return;
        }
    }
}
