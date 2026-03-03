<?php

declare(strict_types=1);

namespace App\Tests\Integration\Gateway\Api\V1\Log\Ingest;

use App\Log\Message\ProcessLogMessage;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

final class ActionTest extends WebTestCase
{
    public function testSuccessfulLogIngestion(): void
    {
        $client = self::createClient();

        $payload = [
            'logs' => [
                [
                    'timestamp' => '2026-02-26T10:30:45+00:00',
                    'level' => 'error',
                    'service' => 'auth-service',
                    'message' => 'User authentication failed',
                    'context' => ['user_id' => 123, 'ip' => '192.168.1.1'],
                    'trace_id' => 'abc123def456',
                ],
                [
                    'timestamp' => '2026-02-26T10:30:46+00:00',
                    'level' => 'info',
                    'service' => 'api-gateway',
                    'message' => 'Request processed',
                    'context' => ['endpoint' => '/api/users', 'method' => 'GET'],
                    'trace_id' => 'abc123def456',
                ],
            ],
        ];

        $client->request('POST', '/api/v1/logs/ingest', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($payload));

        $response = $client->getResponse();

        $this->assertEquals(202, $response->getStatusCode());

        $body = json_decode($response->getContent(), true);

        $this->assertTrue($body['success']);
        $this->assertStringStartsWith('batch_', $body['data']['batch_id']);
        $this->assertEquals(2, $body['data']['logs_count']);

        /** @var InMemoryTransport $transport */
        $transport = self::getContainer()->get('messenger.transport.logs_ingest');
        $messages = $transport->getSent();

        $this->assertCount(2, $messages);
        $this->assertInstanceOf(ProcessLogMessage::class, $messages[0]->getMessage());
        $this->assertEquals('error', $messages[0]->getMessage()->level);
        $this->assertEquals('auth-service', $messages[0]->getMessage()->service);
    }

    public function testReturns400ForInvalidJson(): void
    {
        $client = self::createClient();

        $client->request('POST', '/api/v1/logs/ingest', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], 'not-json');

        $response = $client->getResponse();

        $this->assertEquals(400, $response->getStatusCode());

        $body = json_decode($response->getContent(), true);

        $this->assertFalse($body['success']);
        $this->assertStringContainsString('invalid', strtolower((string) $body['message']));
    }

    public function testReturns400ForEmptyLogsBatch(): void
    {
        $client = self::createClient();

        $client->request('POST', '/api/v1/logs/ingest', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['logs' => []]));

        $response = $client->getResponse();

        $this->assertEquals(400, $response->getStatusCode());

        $body = json_decode($response->getContent(), true);

        $this->assertFalse($body['success']);
        $this->assertNotEmpty($body['errors']);
    }

    public function testReturns400ForMissingRequiredFields(): void
    {
        $client = self::createClient();

        $payload = [
            'logs' => [
                [
                    'level' => 'error',
                    'service' => 'auth-service',
                ],
            ],
        ];

        $client->request('POST', '/api/v1/logs/ingest', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($payload));

        $response = $client->getResponse();

        $this->assertEquals(400, $response->getStatusCode());

        $body = json_decode($response->getContent(), true);

        $this->assertFalse($body['success']);
        $this->assertGreaterThanOrEqual(2, count($body['errors']));
    }

    public function testReturns400ForInvalidLogLevel(): void
    {
        $client = self::createClient();

        $payload = [
            'logs' => [
                [
                    'timestamp' => '2026-02-26T10:30:45+00:00',
                    'level' => 'invalid_level',
                    'service' => 'auth-service',
                    'message' => 'Some message',
                ],
            ],
        ];

        $client->request('POST', '/api/v1/logs/ingest', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($payload));

        $response = $client->getResponse();

        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testOptionalFieldsAreAccepted(): void
    {
        $client = self::createClient();

        $payload = [
            'logs' => [
                [
                    'timestamp' => '2026-02-26T10:30:45+00:00',
                    'level' => 'info',
                    'service' => 'auth-service',
                    'message' => 'Minimal log entry',
                ],
            ],
        ];

        $client->request('POST', '/api/v1/logs/ingest', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($payload));

        $response = $client->getResponse();

        $this->assertEquals(202, $response->getStatusCode());

        $body = json_decode($response->getContent(), true);

        $this->assertTrue($body['success']);
        $this->assertEquals(1, $body['data']['logs_count']);
    }

    public function testPublishedMessagesContainCorrectMetadata(): void
    {
        $client = self::createClient();

        $payload = [
            'logs' => [
                [
                    'timestamp' => '2026-02-26T10:30:45+00:00',
                    'level' => 'error',
                    'service' => 'auth-service',
                    'message' => 'Test message',
                    'context' => ['key' => 'value'],
                    'trace_id' => 'trace123',
                ],
            ],
        ];

        $client->request('POST', '/api/v1/logs/ingest', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($payload));

        /** @var InMemoryTransport $transport */
        $transport = self::getContainer()->get('messenger.transport.logs_ingest');
        $messages = $transport->getSent();

        $this->assertCount(1, $messages);

        /** @var ProcessLogMessage $message */
        $message = $messages[0]->getMessage();

        $this->assertStringStartsWith('batch_', $message->batchId);
        $this->assertNotEmpty($message->publishedAt);
        $this->assertEquals(0, $message->retryCount);
        $this->assertEquals('trace123', $message->traceId);
        $this->assertEquals(['key' => 'value'], $message->context);
    }
}
