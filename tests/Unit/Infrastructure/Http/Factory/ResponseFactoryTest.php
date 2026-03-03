<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Http\Factory;

use App\Infrastructure\Http\Factory\ResponseFactory;
use App\Log\DTO\LogBatchResponseDTO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

final class ResponseFactoryTest extends TestCase
{
    private ResponseFactory $factory;

    protected function setUp(): void
    {
        $serializer = new Serializer(
            [new ObjectNormalizer()],
            [new JsonEncoder()],
        );

        $this->factory = new ResponseFactory($serializer);
    }

    public function testSuccessReturns200WithData(): void
    {
        $response = $this->factory->success(['key' => 'value']);

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getContent(), true);

        $this->assertTrue($body['success']);
        $this->assertEquals(['key' => 'value'], $body['data']);
        $this->assertNull($body['message']);
    }

    public function testSuccessWithMessage(): void
    {
        $response = $this->factory->success([], 'Done');

        $body = json_decode($response->getContent(), true);

        $this->assertEquals('Done', $body['message']);
    }

    public function testAcceptedReturns202(): void
    {
        $response = $this->factory->accepted(['id' => 1]);

        $this->assertEquals(202, $response->getStatusCode());

        $body = json_decode($response->getContent(), true);

        $this->assertTrue($body['success']);
    }

    public function testAcceptedNormalizesDto(): void
    {
        $dto = new LogBatchResponseDTO(batchId: 'batch_123', logsCount: 5);

        $response = $this->factory->accepted($dto);

        $body = json_decode($response->getContent(), true);

        $this->assertEquals('batch_123', $body['data']['batchId']);
        $this->assertEquals(5, $body['data']['logsCount']);
    }

    public function testFailReturns400ByDefault(): void
    {
        $response = $this->factory->fail('Something went wrong');

        $this->assertEquals(400, $response->getStatusCode());

        $body = json_decode($response->getContent(), true);

        $this->assertFalse($body['success']);
        $this->assertEquals('Something went wrong', $body['message']);
        $this->assertEmpty($body['errors']);
    }

    public function testFailWithCustomCode(): void
    {
        $response = $this->factory->fail('Not found', [], 404);

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testFailWithErrors(): void
    {
        $errors = [
            ['field' => 'name', 'message' => 'Required'],
        ];

        $response = $this->factory->fail('Validation error', $errors);

        $body = json_decode($response->getContent(), true);

        $this->assertCount(1, $body['errors']);
        $this->assertEquals('name', $body['errors'][0]['field']);
    }

    public function testValidationFailedFormatsViolations(): void
    {
        $violations = new ConstraintViolationList([
            new ConstraintViolation('Required', null, [], null, 'logs[0].timestamp', ''),
            new ConstraintViolation('Invalid', null, [], null, 'logs[0].level', 'bad'),
        ]);

        $response = $this->factory->validationFailed($violations);

        $this->assertEquals(400, $response->getStatusCode());

        $body = json_decode($response->getContent(), true);

        $this->assertFalse($body['success']);
        $this->assertEquals('Validation failed.', $body['message']);
        $this->assertCount(2, $body['errors']);
        $this->assertEquals('logs[0].timestamp', $body['errors'][0]['field']);
        $this->assertEquals('Required', $body['errors'][0]['message']);
    }

    public function testValidationFailedWithEmptyViolations(): void
    {
        $violations = new ConstraintViolationList();

        $response = $this->factory->validationFailed($violations);

        $body = json_decode($response->getContent(), true);

        $this->assertEmpty($body['errors']);
    }

    public function testSuccessWithEmptyArray(): void
    {
        $response = $this->factory->success([]);

        $body = json_decode($response->getContent(), true);

        $this->assertEquals([], $body['data']);
    }
}
