<?php

declare(strict_types=1);

namespace Tests\Unit\WebServCo\Http\Client\Service\PSR18\HttpClient\Discogs\Releases;

use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Unit\Assets\Test\Discogs\AbstractDiscogsTestClass;
use WebServCo\Http\Client\Service\PSR18\HttpClient;

use function sprintf;

#[CoversClass(HttpClient::class)]
final class DiscogsPsr11ReleasesTest extends AbstractDiscogsTestClass
{
    public function test11ReleasesStatusCode(): void
    {
        $lapTimer = $this->createLapTimer();
        $lapTimer->start();
        $lapTimer->lap('begin');

        $logger = $this->createLogger(__METHOD__);

        foreach (self::RELEASE_IDS_11 as $releaseId) {
            $statusCode = $this->getGetResponseStatusCodeByUrl(
                3,
                sprintf('%sreleases/%d', self::DISCOGS_API_URL, $releaseId),
            );
            $lapTimer->lap(sprintf('r %d', $releaseId));

            self::assertSame(200, $statusCode);
        }

        $lapTimer->lap('end');

        $logger->info('Lap stats', $lapTimer->getStatistics());
    }
}
