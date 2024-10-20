<?php

declare(strict_types=1);

namespace Tests\Unit\WebServCo\Http\Client\Service\cURL\CurlService\Discogs\Releases\RateLimiting;

use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Http\Client\ClientExceptionInterface;
use Tests\Unit\Assets\Test\Discogs\AbstractDiscogsTestClass;
use WebServCo\Http\Client\Service\cURL\CurlService;

use function is_int;
use function sleep;
use function sprintf;

#[CoversClass(CurlService::class)]
final class ManyReleasesIndividualRateLimitingTest extends AbstractDiscogsTestClass
{
    private const int NUMBER_OF_RELEASES = 1000;
    private const int TIMEOUT = 30;

    /**
     * Cutoff time in seconds.
     * Should be fixed to 60, but use constant to be able to easily test different values.
     */
    private const int WAITING_TIME = 60;

    /**
     * @phpcs:disable SlevomatCodingStandard.Complexity.Cognitive.ComplexityTooHigh
     * @phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
     */
    public function testManyReleasesOneByOneWithRateLimiting(): void
    {
        // Log
        $logger = $this->createLogger(__FUNCTION__);
        $logger->debug(__METHOD__);

        // Create CurlService.
        // We will use a custom implementation, and not the PSR-18 HTTP Client, for control and performance.
        $curlService = $this->createCurlService(self::TIMEOUT);

        // Get list of items
        $releaseIds = $this->getReleaseIds(self::NUMBER_OF_RELEASES);

        // Initialize rate limit values.
        $rateLimitTotal = $rateLimitRemaining = null;

        // Loop items
        foreach ($releaseIds as $releaseId) {
            $logger->debug(sprintf('Process release %d.', $releaseId));

            if (is_int($rateLimitRemaining) && $rateLimitRemaining <= 1) {
                $logger->debug(
                    sprintf('ratelimit-remaining: %d. Waiting %d seconds.', $rateLimitRemaining, self::WAITING_TIME),
                );
                // Rate limit reached.
                sleep(self::WAITING_TIME);
            }

            // Create request.
            $request = $this->createGetRequest(sprintf('%sreleases/%d', $this->getDiscogsApiUrl(), $releaseId));

            $curlHandle = $curlService->createHandle($request);

            try {
                $responseContent = $curlService->executeCurlSession($curlHandle);

                $response = $curlService->getResponse($curlHandle, $responseContent);

                // Get rate limit values
                $rateLimitTotal = (int) $response->getHeaderLine('x-discogs-ratelimit');
                $rateLimitRemaining = (int) $response->getHeaderLine('x-discogs-ratelimit-remaining');
                $statusCode = $response->getStatusCode();
            } catch (ClientExceptionInterface $exception) {
                $logger->error($exception->getMessage(), ['exception' => $exception]);
                $rateLimitTotal = $rateLimitRemaining = null;
                $statusCode = $exception->getCode();
                /** @todo handle exception */
            }

            // Log
            $logger->debug(sprintf('Release %d: status: %d.', $releaseId, $statusCode));
            $logger->debug(sprintf('Release %d: ratelimit-total: %d.', $releaseId, $rateLimitTotal));
            $logger->debug(sprintf('Release %d: ratelimit-remaining: %d.', $releaseId, $rateLimitRemaining));

            unset($curlHandle);

            $curlService->reset();

            // Validate response.
            // Accept also 404 because release id's are not really consecutive (releases can be deleted).
            self::assertContains($statusCode, [200, 404]);

            /** Response processing would go here. */
        }

        $logger->debug('Complete.');
    }
    // @phpcs:enable
}
