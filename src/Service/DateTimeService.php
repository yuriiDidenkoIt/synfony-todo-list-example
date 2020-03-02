<?php

namespace App\Service;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Class DateTimeService
 */
class DateTimeService
{
    public const SECONDS_IN_MINUTE = 60;
    public const SECONDS_IN_HOUR = 60 * self::SECONDS_IN_MINUTE;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * DateTimeService constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return int
     */
    public function now(): int
    {
        try {
            $nowDate = new DateTime();
            $now = $nowDate->getTimestamp();
        } catch (Throwable $exception) {
            $now = time();
            $this->logger->warning($exception->getMessage());
        }

        return $now;
    }

    /**
     * @return DateTimeImmutable
     */
    public function nowDateTime(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }

    /**
     * @param DateTimeInterface $dateTime
     *
     * @return bool
     */
    public function isPast(DateTimeInterface $dateTime): bool
    {
        return $dateTime->getTimestamp() < $this->now();
    }
}