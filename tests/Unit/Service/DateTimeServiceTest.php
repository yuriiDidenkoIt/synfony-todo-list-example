<?php

namespace App\Tests\Unit\Service;

use App\Service\DateTimeService;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Class DateTimeServiceTest
 */
class DateTimeServiceTest extends TestCase
{
    /**
     * @var DateTimeService
     */
    private $service;

    protected function setUp(): void
    {
        $this->service = new DateTimeService(new NullLogger());
    }

    public function testNow(): void
    {
        $this->assertNotEmpty($this->service->now());
    }

    public function testNowDateTime(): void
    {
        $actual = $this->service->nowDateTime();
        $this->assertNotEmpty($actual);
        $this->assertInstanceOf(DateTimeImmutable::class, $actual);
    }

    public function testIsPast(): void
    {
        $notPast = (new \DateTime())->modify('+1 hour');
        $this->assertFalse($this->service->isPast($notPast));

        $past = (new \DateTime())->modify('-1 hour');
        $this->assertTrue($this->service->isPast($past));
    }
}