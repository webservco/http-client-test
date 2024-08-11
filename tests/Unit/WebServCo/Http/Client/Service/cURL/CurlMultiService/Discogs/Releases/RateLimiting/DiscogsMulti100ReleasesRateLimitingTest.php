<?php

declare(strict_types=1);

namespace Tests\Unit\WebServCo\Http\Client\Service\cURL\CurlMultiService\Discogs\Releases\RateLimiting;

use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Http\Client\ClientExceptionInterface;
use Tests\Unit\Assets\Test\Discogs\AbstractDiscogsTestClass;
use WebServCo\Http\Client\Service\cURL\CurlMultiService;

use function array_chunk;
use function array_shift;
use function array_splice;
use function array_unshift;
use function sleep;
use function sprintf;
use function time;

/**
 * @todo work
 * Test Multi system with Discogs rate limiting.
 * Target: use 100 releases and process all (test should not fail) despite limit of 25.
 */
#[CoversClass(CurlMultiService::class)]
final class DiscogsMulti100ReleasesRateLimitingTest extends AbstractDiscogsTestClass
{
    /**
     * @phpcs:disable SlevomatCodingStandard.Complexity.Cognitive.ComplexityTooHigh
     * @phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testReleasesRateLimiting(): void
    {
        $lapTimer = $this->createLapTimer();
        $lapTimer->start();
        $lapTimer->lap('begin');

        $logger = $this->createLogger(__METHOD__);
        $logger->debug(__METHOD__);

        $curlMultiService = $this->createCurlMultiService(3);

        // Keep a list of handles to be able to link them to each id. key: id, value: handle identifier.
        $curlHandleIdentifiers = [];

        // Get list of release ids
        $releaseIds = $this->getReleaseIds(100);
        $firstReleaseId = array_shift($releaseIds);

        // Note: now $releaseIds contains less items.

        // Initialize rate limit values.
        $ratelimitTotal = $ratelimitRemaining = 0;

        // Note the start time.
        $timeRateLimit = time();
        $logger->debug(sprintf('RL: timeRateLimit: %d', $timeRateLimit));

        // Execute first request separately, in order to check rate limiting.
        try {
            $response = $this->getGetResponse(3, sprintf('%sreleases/%d', self::DISCOGS_API_URL, $firstReleaseId));
            $statusCode = $response->getStatusCode();

            // Get rate limit values
            $ratelimitTotal = (int) $response->getHeaderLine('x-discogs-ratelimit');
            $ratelimitRemaining = (int) $response->getHeaderLine('x-discogs-ratelimit-remaining');
        } catch (ClientExceptionInterface $exception) {
            $statusCode = $exception->getCode();
        }

        // Log
        $logger->debug(sprintf('R1: statusCode: %d', $statusCode));
        $logger->debug(sprintf('RL: ratelimitTotal: %d', $ratelimitTotal));
        $logger->debug(sprintf('RL: ratelimitRemaining: %d', $ratelimitRemaining));

        // Validate first response.
        self::assertSame(200, $statusCode);
        self::assertSame(25, $ratelimitTotal);
        // Somehow, after making one request, the limit is still 25.
        self::assertSame(25, $ratelimitRemaining);

        /**
         * We will split the items in chunks as follows:
         * - first chunk: as many items as the remaining rate limit
         * - all other chunks: as many items as the total rate limit
         */

        // Process first chunk
        $firstChunk = array_splice($releaseIds, 0, $ratelimitRemaining);

        // Note: now $releaseIds contains less items.

        // All other chunks: Split the remaining array by total rate limit
        $chunks = array_chunk($releaseIds, $ratelimitTotal);

        // From this point on $releaseIds is not used any more.

        // Add first chunk at the beginning of the chunks array.
        array_unshift($chunks, $firstChunk);

        // Now $chunks contains all the data split by rate limiting.

        $logger->debug('RL: processing chunks.');

        // Iterate chunks
        foreach ($chunks as $index => $chunk) {
            $logger->debug(sprintf('RL: processing chunk at index %d.', $index));


            $logger->debug('Creating requests.');

            // Each chunk contains a list of items to process.
            foreach ($chunk as $releaseId) {
                // Create request.
                $request = $this->createGetRequest(sprintf('%sreleases/%d', self::DISCOGS_API_URL, $releaseId));
                // Create handle and add it's identifier to the list.
                $handleIdentifier = $curlMultiService->createHandle($request);
                $curlHandleIdentifiers[$releaseId] = $handleIdentifier;
            }

            $logger->debug('Executing sessions.');

            // Execute sessions.
            $curlMultiService->executeSessions();

            // Check rate limits.
            $logger->debug('Handling rate limiting.');

            // Get current time.
            $timeCurrentChunk = time();
            $logger->debug(sprintf('RL: timeCurrentChunk: %d', $timeCurrentChunk));
            $logger->debug(sprintf('RL: timeRateLimit: %d', $timeRateLimit));

            // Check how many seconds have passed since last chunk.
            $elapsedTime = $timeCurrentChunk - $timeRateLimit;
            $logger->debug(sprintf('RL: elapsedTime: %d', $elapsedTime));

            // Set new time for the next chunk.
            $timeRateLimit = time();

            // We can only call the API again after 1 minute has passed.
            if ($elapsedTime >= 60) {
                $logger->debug('RL: elapsedTime more than cutoff, nothing to do.');

                // More than cutoff time has passed, nothing else to do here.
                continue;
            }

            // Less than cutoff time has passed, we need to wait the difference.
            $difference = 60 - $elapsedTime;
            $logger->debug(sprintf('RL: elapsedTime under cutoff, waiting: %d seconds.', $difference));
            sleep($difference);
        }

        $logger->debug('Processing responses.');

        // Iterate responses
        foreach ($curlHandleIdentifiers as $releaseId => $handleIdentifier) {
            try {
                // Not using getMultiResponseStatusCode because we need the response
                $response = $curlMultiService->getResponse($handleIdentifier);

                $statusCode = $response->getStatusCode();
            } catch (ClientExceptionInterface $exception) {
                $statusCode = $exception->getCode();
            }

            $logger->debug(sprintf('r %d, status: %d', $releaseId, $statusCode));

            $lapTimer->lap(sprintf('r %d', $releaseId));

            // Validate response.
            self::assertSame(200, $statusCode);
        }

        $logger->debug('Cleanup.');

        // Cleanup. After this the service can be re-used, going through all the steps.
        $curlMultiService->cleanup();

        $lapTimer->lap('end');

        $logger->info('Lap stats', $lapTimer->getStatistics());
    }
    // @phpcs:enable

    /**
     * @return array<int,int>
     */
    private function getReleaseIds(int $numberOfItems): array
    {
        $data = [];
        for ($releaseId = 1; $releaseId <= $numberOfItems; $releaseId += 1) {
            $data[] = $releaseId;
        }

        return $data;
    }
}
