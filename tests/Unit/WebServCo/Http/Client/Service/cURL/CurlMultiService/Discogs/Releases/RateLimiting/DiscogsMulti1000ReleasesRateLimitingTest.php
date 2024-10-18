<?php

declare(strict_types=1);

namespace Tests\Unit\WebServCo\Http\Client\Service\cURL\CurlMultiService\Discogs\Releases\RateLimiting;

use OutOfBoundsException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DependsOnClass;
use Psr\Http\Client\ClientExceptionInterface;
use Tests\Unit\Assets\Test\Discogs\AbstractDiscogsTestClass;
use Tests\Unit\WebServCo\Http\Client\Service\cURL\CurlMultiService\Discogs\Releases\DiscogsMulti11ReleasesTest;
use UnexpectedValueException;
use WebServCo\Http\Client\Service\cURL\CurlMultiService;

use function array_chunk;
use function array_key_exists;
use function array_shift;
use function array_splice;
use function array_unshift;
use function count;
use function is_int;
use function sleep;
use function sprintf;
use function time;

/**
 * @todo work
 * Test Multi system with Discogs rate limiting.
 * Target: use 100 releases and process all (test should not fail) despite limit of 60.
 */
#[CoversClass(CurlMultiService::class)]
final class DiscogsMulti1000ReleasesRateLimitingTest extends AbstractDiscogsTestClass
{
    /**
     * Cutoff time in seconds.
     * Should be fixed to 60, but use constant to be able to easily test different values.
     */
    private const int CUTOFF_TIME = 60;

    private const int TIMEOUT = 30;

    private const int WAITING_TIME_ADJUSTMENT = 1;

    /**
     * @phpcs:disable SlevomatCodingStandard.Complexity.Cognitive.ComplexityTooHigh
     * @phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    #[DependsOnClass(DiscogsMulti11ReleasesTest::class)]
    public function testReleasesRateLimiting(): void
    {
        $lapTimer = $this->createLapTimer();
        $lapTimer->start();
        $lapTimer->lap('begin');

        $logger = $this->createLogger(__FUNCTION__);
        $logger->debug(__METHOD__);

        $curlMultiService = $this->createCurlMultiService(self::TIMEOUT);

        // Get list of release ids
        $releaseIds = $this->getReleaseIds(1000);
        $firstReleaseId = array_shift($releaseIds);
        if (!is_int($firstReleaseId)) {
            throw new UnexpectedValueException('Invalid release id.');
        }

        // Now $releaseIds contains fewer items.

        // Initialize rate limit values.
        $ratelimitTotal = $ratelimitRemaining = 0;

        // Note the start time.
        $timeRateLimit = time();
        $logger->debug(sprintf('RL: initial timeRateLimit: %d.', $timeRateLimit));

        // Execute first request separately, in order to check rate limiting.
        try {
            $logger->debug('Get the first release.');
            $response = $this->getGetResponse(
                self::TIMEOUT,
                sprintf('%sreleases/%d', $this->getDiscogsApiUrl(), $firstReleaseId),
            );
            $statusCode = $response->getStatusCode();
            // Rate limiting check for first request (1).
            if ($statusCode === 429) {
                /**
                 * Response code 429 means rate limit was reached (other requests were made before we got here).
                 * Header should be: < x-discogs-ratelimit-remaining: 0
                 * Response body should be:
                 * '{"message":"You are making requests too quickly."}
                 *
                 * '
                 *
                 * In this case we can wait 1 minute and try again from the start
                 */
                $logger->debug(sprintf('Response code is 429, waiting %d seconds.', self::CUTOFF_TIME));
                sleep(self::CUTOFF_TIME);
                $logger->debug('Trying again to get the first release.');
                /** @todo this should be a separate recursive function, with N number of tries. */
                $response = $this->getGetResponse(
                    3,
                    sprintf('%sreleases/%d', $this->getDiscogsApiUrl(), $firstReleaseId),
                );
                $statusCode = $response->getStatusCode();
            }

            // Get rate limit values
            $ratelimitTotal = (int) $response->getHeaderLine('x-discogs-ratelimit');
            $ratelimitRemaining = (int) $response->getHeaderLine('x-discogs-ratelimit-remaining');
        } catch (ClientExceptionInterface $exception) {
            $logger->error($exception->getMessage(), ['exception' => $exception]);
            $statusCode = $exception->getCode();
        }

        // Log
        $logger->debug(sprintf('R1: statusCode: %d.', $statusCode));
        $logger->debug(sprintf('RL: ratelimitTotal: %d.', $ratelimitTotal));
        $logger->debug(sprintf('RL: ratelimitRemaining: %d.', $ratelimitRemaining));

        // Validate first response.
        self::assertSame(200, $statusCode);

        // Rate limiting check for first request (2).
        /**
         * Situation: when making the first request, no other requests were made.
         * the limit is "60" (after making a request),
         * so in reality we can still make "59" requests, despite Discogs saying "60".
         *
         * Adjust rate limits: sacrifice 1 request to make sure all is OK
         */
        $ratelimitTotal -= 1;
        $ratelimitRemaining -= 1;
        $logger->debug(sprintf('RL: ratelimitTotal (adjusted): %d.', $ratelimitTotal));
        $logger->debug(sprintf('RL: ratelimitRemaining (adjusted): %d.', $ratelimitRemaining));

        // Handle limits adjustment.
        self::assertGreaterThanOrEqual(1, $ratelimitTotal, 'ratelimitTotal must be greater than 1.');
        if ($ratelimitTotal < 1) {
            // This is needed for static analysis (PHPStan).
            throw new OutOfBoundsException('ratelimitTotal must be greater than 1.');
        }
        self::assertGreaterThanOrEqual(0, $ratelimitRemaining, 'ratelimitRemaining must be greater than 0.');

        /**
         * Another situation: when making the first request, other requests were made.
         */
        if ($ratelimitRemaining !== $ratelimitTotal) {
            // Some requests were already made, implement rate limiting.

            // Check for 0 and 1
            // 0 should be handled above, however it can be also be the result of adjustment.
            // 1 actually means there are no more requests available.
            // Any other value higher than 1 will be handled below (added to first chunk).
            if ($ratelimitRemaining === 0 || $ratelimitRemaining === 1) {
                // Since the requests were made externally, we have no way to measure the elapsed time,
                // so we need to wait a full minute.
                /** @todo this should be solved if using shared memory to keep track of limits and time elapsed */
                sleep(self::CUTOFF_TIME);
            }
        }

        /**
         * Rate Limiting.
         *
         * We will split the items in chunks as follows:
         * - first chunk: as many items as the remaining rate limit
         * - all other chunks: as many items as the total rate limit
         */

        // Work on a copy of $releaseIds, because it will be modified.
        $releaseIdsClone = $releaseIds;

        // Process first chunk. "Numerical keys in array are not preserved."
        $firstChunk = array_splice($releaseIdsClone, 0, $ratelimitRemaining);

        // Note: now $releaseIdsClone contains less items.

        // All other chunks: Split the remaining array by total rate limit
        // preserve_keys defaults to false
        $chunks = array_chunk($releaseIdsClone, $ratelimitTotal, false);

        // From this point on $releaseIdsClone is not used anymore.
        unset($releaseIdsClone);

        // Add first chunk at the beginning of the chunks array.
        // "All numerical array keys will be modified to start counting from zero while literal keys won't be changed."
        array_unshift($chunks, $firstChunk);

        // Now $chunks contains all the data split by rate limiting.

        // Keep a list of handles to be able to link them to each id. key: id, value: handle identifier.
        $curlHandleIdentifiers = [];

        $logger->debug('Processing chunks.');

        foreach ($chunks as $index => $chunk) {
            // Each chunk contains a list of items to process.

            $timeStartCurrentChunk = time();
            $logger->debug(sprintf('RL: last timeRateLimit: %d.', $timeRateLimit));
            $logger->debug(sprintf('RL: timeStartCurrentChunk (%d): %d.', $index, $timeStartCurrentChunk));

            // Check how many seconds have passed since last chunk processing.
            $elapsedTime = $timeStartCurrentChunk - $timeRateLimit;
            $logger->debug(sprintf('RL: elapsedTime since last processing: %d.', $elapsedTime));

            // We can only call the API again after enough time has passed.
            // Check elapsed time, but only if not first chunk (nothing to wait after).
            if ($index > 0 && $elapsedTime < self::CUTOFF_TIME) {
                // Less than cutoff time has passed, we need to wait the difference.
                $waitingTime = self::CUTOFF_TIME - $elapsedTime;
                $logger->debug(sprintf('RL: waitingTime: %d', $waitingTime));
                // Adjust
                $waitingTime += self::WAITING_TIME_ADJUSTMENT;

                $logger->debug(sprintf('RL: waitingTime (adjusted): %d.', $waitingTime));
                $logger->debug(sprintf('RL: elapsedTime under cutoff, waiting: %d seconds.', $waitingTime));
                sleep($waitingTime);
            }

            // Set new time for the next chunk. This is the actual start time of the current chunk, after waiting.
            $timeRateLimit = time();
            $logger->debug(sprintf('RL: updated timeRateLimit after chunk %d: %d.', $index, $timeRateLimit));

            $logger->debug(sprintf('Creating requests; chunk %d, %d items.', $index, count($chunk)));
            foreach ($chunk as $releaseId) {
                // Note: requests are executed in parallel, not one by one based on our release list.

                // Create request.
                $request = $this->createGetRequest(sprintf('%sreleases/%d', $this->getDiscogsApiUrl(), $releaseId));
                // Create handle and add it's identifier to the list.
                $handleIdentifier = $curlMultiService->createHandle($request);
                $curlHandleIdentifiers[$releaseId] = $handleIdentifier;
                $logger->debug(sprintf('Created handle: release %d, handle %s.', $releaseId, $handleIdentifier));
            }

            $logger->debug(sprintf('Executing sessions; chunk %d.', $index));

            // Execute sessions.
            $curlMultiService->executeSessions();

            $logger->debug(sprintf('Processing responses; chunk %d.', $index));
            foreach ($chunk as $releaseId) {
                // Get response.
                $logger->debug(sprintf('Get response: release %d.', $releaseId));

                if (!array_key_exists($releaseId, $curlHandleIdentifiers)) {
                    throw new OutOfBoundsException(sprintf('No cURL handle for release %d.', $releaseId));
                }

                $ratelimitUsed = $ratelimitRemaining = null;
                try {
                    $response = $curlMultiService->getResponse($curlHandleIdentifiers[$releaseId]);

                    $statusCode = $response->getStatusCode();
                    // Get rate limit values
                    $ratelimitUsed = (int) $response->getHeaderLine('x-discogs-ratelimit-used');
                    $ratelimitRemaining = (int) $response->getHeaderLine('x-discogs-ratelimit-remaining');
                } catch (ClientExceptionInterface $exception) {
                    $logger->error($exception->getMessage(), ['exception' => $exception]);
                    $statusCode = $exception->getCode();
                }

                $logger->debug(sprintf('Release %d: status: %d.', $releaseId, $statusCode));
                if ($ratelimitUsed !== null) {
                    $logger->debug(sprintf('Release %d: ratelimit-used: %d.', $releaseId, $ratelimitUsed));
                }
                if ($ratelimitRemaining !== null) {
                    $logger->debug(sprintf('Release %d: ratelimit-remaining: %d.', $releaseId, $ratelimitRemaining));
                }

                $lapTimer->lap(sprintf('Release %d', $releaseId));

                // Validate response.
                // Accept also 404 because release id's are not really consecutive (releases can be deleted).
                self::assertContains($statusCode, [200, 404]);

                /** Response processing would go here. */
            }

            // Reset. After this the service can be re-used, going through all the steps.
            $curlMultiService->reset();

            $curlHandleIdentifiers = [];

            $logger->debug(sprintf('Cleanup; chunk %d.', $index));

            // Check rate limits.
            $logger->debug(sprintf('Handling rate limiting; chunk %d.', $index));

            // Get current time.
            $timeEndCurrentChunk = time();
            $logger->debug(sprintf('RL: timeEndCurrentChunk (%d): %d', $index, $timeEndCurrentChunk));

            $lapTimer->lap(sprintf('chunk %d', $index));
        }

        $lapTimer->lap('end');

        $logger->info('Lap stats.', $lapTimer->getStatistics());
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
