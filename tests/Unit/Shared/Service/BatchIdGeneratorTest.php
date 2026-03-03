<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Service;

use App\Shared\Service\BatchIdGenerator;
use PHPUnit\Framework\TestCase;

final class BatchIdGeneratorTest extends TestCase
{
    private BatchIdGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new BatchIdGenerator();
    }

    public function testGeneratesIdWithBatchPrefix(): void
    {
        $id = $this->generator->generate();

        $this->assertStringStartsWith('batch_', $id);
    }

    public function testGeneratesIdWithCorrectFormat(): void
    {
        $id = $this->generator->generate();

        $this->assertMatchesRegularExpression('/^batch_[a-f0-9]{32}$/', $id);
    }

    public function testGeneratesUniqueIds(): void
    {
        $ids = [];
        for ($i = 0; $i < 100; ++$i) {
            $ids[] = $this->generator->generate();
        }

        $this->assertCount(100, array_unique($ids));
    }
}
