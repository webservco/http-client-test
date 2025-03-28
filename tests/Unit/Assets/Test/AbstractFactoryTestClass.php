<?php

declare(strict_types=1);

namespace Tests\Unit\Assets\Test;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tests\Unit\Assets\Factory\CurlServiceFactory;
use UnexpectedValueException;
use WebServCo\Http\Client\Contract\Service\cURL\CurlMultiServiceInterface;
use WebServCo\Http\Client\Contract\Service\cURL\CurlServiceInterface;
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

/**
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
abstract class AbstractFactoryTestClass extends TestCase
{
    protected ?CurlServiceFactory $curlServiceFactory = null;

    private ?LapTimerFactoryInterface $lapTimerFactory = null;

    private ?ContextFileLoggerFactory $loggerFactory = null;

    /**
     * Create CurlMultiService.
     */
    protected function createCurlMultiService(int $timeout): CurlMultiServiceInterface
    {
        $curlService = $this->createCurlService($timeout);

        return new CurlMultiService($curlService);
    }

    /**
     * Create CurlService
     *
     * Initialized each time, however factory only once.
     */
    protected function createCurlService(int $timeout): CurlServiceInterface
    {
        if ($this->curlServiceFactory === null) {
            $this->curlServiceFactory = new CurlServiceFactory($this->getLoggerFactory());
        }

        return $this->curlServiceFactory->createCurlService($timeout);
    }

    /**
     * Create PSR-18 HTTP Client.
     */
    protected function createHttpClient(int $timeout): HttpClient
    {
        $curlService = $this->createCurlService($timeout);

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
        $projectPath = realpath(__DIR__ . '/../../../../');
        if ($projectPath === false) {
            throw new UnexpectedValueException('Failed to retrieve path.');
        }

        return $projectPath . DIRECTORY_SEPARATOR;
    }
}
