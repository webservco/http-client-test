<?php

declare(strict_types=1);

namespace Tests\Unit\WebServCo\Http\Client\Service\cURL\CurlService\Discogs\Releases\RateLimiting;

use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Http\Client\ClientExceptionInterface;
use Tests\Unit\Assets\Test\Discogs\AbstractDiscogsTestClass;
use WebServCo\Http\Client\Service\cURL\CurlService;

use function count;
use function sleep;
use function sprintf;

#[CoversClass(CurlService::class)]
final class ManyReleasesIndividualRateLimitingTest extends AbstractDiscogsTestClass
{
    /**
     * Time to delay calls execution when rate limit is reached.
     */
    private const int DELAY_TIME = 2;

    private const int NUMBER_OF_RELEASES = 1000;
    private const int TIMEOUT = 30;

    /**
     * @phpcs:disable SlevomatCodingStandard.Complexity.Cognitive.ComplexityTooHigh
     * @phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
     */
    public function skipManyReleasesOneByOneWithRateLimiting(): void
    {
        // Log
        $logger = $this->createLogger(__FUNCTION__);
        $logger->debug(__METHOD__);

        // Create CurlService.
        // We will use a custom implementation, and not the PSR-18 HTTP Client, for control and performance.
        $curlService = $this->createCurlService(self::TIMEOUT);

        // Get list of items
        $releaseIds = $this->getReleaseIds(self::NUMBER_OF_RELEASES);
        $logger->debug(sprintf('Processing %d releases.', count($releaseIds)));

        // Initialize rate limit values. Use null until any value is set.
        $rateLimitRemaining = null;

        // Loop items
        foreach ($releaseIds as $releaseId) {
            $logger->debug(sprintf('Process release %d.', $releaseId));

            // 0 is already status 429
            if ($rateLimitRemaining === 1) {
                $logger->debug('Rate limit exhausted.');

                /**
                 * Do not wait 1 full minute, but just 1-2 seconds, and we get again 1-2 requests.
                 * Note: this is the first approach that actually works (all 1000 releases were processed).
                 * The other option would be to wait a full minute to reset the rate limiting.
                 *
                 * @todo study which approach is the most efficient.
                 */
                $logger->debug(sprintf('Waiting %d seconds.', self::DELAY_TIME));
                sleep(self::DELAY_TIME);
            }

            // Create request.
            $request = $this->createGetRequest(sprintf('%sreleases/%d', $this->getDiscogsApiUrl(), $releaseId));

            $curlHandle = $curlService->createHandle($request);

            try {
                $responseContent = $curlService->executeCurlSession($curlHandle);

                $response = $curlService->getResponse($curlHandle, $responseContent);

                // Get rate limit values
                $rateLimitTotal = (int) $response->getHeaderLine('x-discogs-ratelimit');
                $ratelimitUsed = (int) $response->getHeaderLine('x-discogs-ratelimit-used');
                $rateLimitRemaining = (int) $response->getHeaderLine('x-discogs-ratelimit-remaining');
                $statusCode = $response->getStatusCode();

                $logger->debug(sprintf('Release %d: status: %d.', $releaseId, $statusCode));
                $logger->debug(sprintf('Release %d: rateLimitTotal: %d.', $releaseId, $rateLimitTotal));
                $logger->debug(sprintf('Release %d: ratelimitUsed: %d.', $releaseId, $ratelimitUsed));
                $logger->debug(sprintf('Release %d: rateLimitRemaining: %d.', $releaseId, $rateLimitRemaining));
            } catch (ClientExceptionInterface $exception) {
                $logger->error($exception->getMessage(), ['exception' => $exception]);
                $statusCode = $exception->getCode();
                $logger->debug(sprintf('Release %d: error status: %d.', $releaseId, $statusCode));
                # todo handle exception (NEXT: build actual sys)
            }

            unset($curlHandle);

            $curlService->reset();

            // Validate response.
            // Accept also 404 because release id's are not really consecutive (releases can be deleted).
            # todo handle discogs error, eg. 500 (retry N times) (NEXT: build actual sys). For now, accept that status.
            self::assertContains($statusCode, [200, 404, 500]);

            /** Response processing would go here. */
        }

        $logger->debug('Complete.');
    }
    // @phpcs:enable
}
