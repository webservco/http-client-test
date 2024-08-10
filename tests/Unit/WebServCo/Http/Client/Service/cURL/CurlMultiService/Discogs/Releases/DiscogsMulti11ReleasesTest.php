<?php

declare(strict_types=1);

namespace Tests\Unit\WebServCo\Http\Client\Service\cURL\CurlMultiService\Discogs\Releases;

use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Unit\Assets\Test\Discogs\AbstractDiscogsTestClass;
use WebServCo\Http\Client\Service\cURL\CurlMultiService;

use function sprintf;

#[CoversClass(CurlMultiService::class)]
final class DiscogsMulti11ReleasesTest extends AbstractDiscogsTestClass
{
    public function test11ReleasesStatusCode(): void
    {
        $lapTimer = $this->createLapTimer();
        $lapTimer->start();
        $lapTimer->lap('begin');

        $logger = $this->createLogger(__FUNCTION__);

        $curlMultiService = $this->createCurlMultiService(3);

        // Keep a list of handles to be able to link them to each id. key: id, value: handle identifier.
        $curlHandleIdentifiers = [];

        // Iterate list
        foreach (self::RELEASE_IDS as $releaseId) {
            // Create request.
            $request = $this->createGetRequest(sprintf('%sreleases/%d', self::DISCOGS_API_URL, $releaseId));
            // Create handle and add it's identifier to the list.
            $handleIdentifier = $curlMultiService->createHandle($request);
            $curlHandleIdentifiers[$releaseId] = $handleIdentifier;
        }

        // Execute sessions.
        $curlMultiService->executeSessions();

        // Iterate responses
        foreach ($curlHandleIdentifiers as $releaseId => $handleIdentifier) {
            $statusCode = $this->getMultiResponseStatusCode($curlMultiService, $handleIdentifier);
            $lapTimer->lap(sprintf('r %d', $releaseId));

            self::assertSame(200, $statusCode);
        }

        // Cleanup. After this the service can be re-used, going through all the steps.
        $curlMultiService->cleanup();

        $lapTimer->lap('end');

        $logger->info('Lap stats', $lapTimer->getStatistics());
    }
}
