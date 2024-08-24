<?php

declare(strict_types=1);

namespace Tests\Unit\Assets\Test;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tests\Unit\Assets\Factory\CurlServiceFactory;
use WebServCo\Http\Client\Contract\Service\cURL\CurlMultiServiceInterface;
use WebServCo\Http\Client\Service\cURL\CurlMultiService;
use WebServCo\Http\Client\Service\PSR18\HttpClient;
use WebServCo\Log\Contract\LoggerFactoryInterface;
use WebServCo\Log\Factory\ContextFileLoggerFactory;
use WebServCo\Stopwatch\Contract\LapTimerFactoryInterface;
use WebServCo\Stopwatch\Contract\LapTimerInterface;
use WebServCo\Stopwatch\Factory\LapTimerFactory;

use function realpath;
use function sprintf;

use const DIRECTORY_SEPARATOR;

abstract class AbstractFactoryTestClass extends TestCase
{
    protected ?CurlServiceFactory $curlServiceFactory = null;

    private ?LapTimerFactoryInterface $lapTimerFactory = null;

    private ?ContextFileLoggerFactory $loggerFactory = null;

    /**
     * Create CurlMultiService.
     *
     * Initialized each time, however factory only once.
     */
    protected function createCurlMultiService(int $timeout): CurlMultiServiceInterface
    {
        if ($this->curlServiceFactory === null) {
            $this->curlServiceFactory = new CurlServiceFactory($this->getLoggerFactory());
        }

        $curlService = $this->curlServiceFactory->createCurlService($timeout);

        return new CurlMultiService($curlService);
    }

    /**
     * Create PSR-18 HTTP Client.
     *
     * Initialized each time, however factory only once.
     */
    protected function createHttpClient(int $timeout): HttpClient
    {
        if ($this->curlServiceFactory === null) {
            $this->curlServiceFactory = new CurlServiceFactory($this->getLoggerFactory());
        }

        $curlService = $this->curlServiceFactory->createCurlService($timeout);

        return new HttpClient($curlService);
    }

    /**
     * Lap timer.
     *
     * Used in some tests to measure and log the request/response time.
     *
     * Initialized each time, however factory only once.
     */
    protected function createLapTimer(): LapTimerInterface
    {
        if ($this->lapTimerFactory === null) {
            $this->lapTimerFactory = new LapTimerFactory();
        }

        return $this->lapTimerFactory->createLapTimer();
    }

    protected function createLogger(string $methodName): LoggerInterface
    {
        return $this->getLoggerFactory()->createLogger(
        /**
         * Unorthodox: use a path (http-client/time/handleIdentifier) as channel.
         */
            sprintf(
                '%s%s%s%s%s',
                'test',
                DIRECTORY_SEPARATOR,
                // Use only up to minutes, as requests may spread across seconds
                (new DateTimeImmutable())->format('Ymd.Hi'),
                DIRECTORY_SEPARATOR,
                $methodName,
            ),
        );
    }

    protected function getLoggerFactory(): LoggerFactoryInterface
    {
        if ($this->loggerFactory === null) {
            $projectPath = $this->getProjectPath();

            $this->loggerFactory = new ContextFileLoggerFactory(sprintf('%svar/log', $projectPath));
        }

        return $this->loggerFactory;
    }

    protected function getProjectPath(): string
    {
        return realpath(__DIR__ . '/../../../../') . DIRECTORY_SEPARATOR;
    }
}
