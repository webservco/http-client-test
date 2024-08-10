<?php

declare(strict_types=1);

namespace Tests\Unit\WebServCo\Http\Client\Service\PSR18\HttpClient\Discogs\Release;

use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Unit\Assets\Test\Discogs\AbstractDiscogsTestClass;
use WebServCo\Http\Client\Service\PSR18\HttpClient;

use function sprintf;

#[CoversClass(HttpClient::class)]
final class Discogs1ReleaseTest extends AbstractDiscogsTestClass
{
    public function testGetStatusCode(): void
    {
        $statusCode = $this->getGetResponseStatusCodeByUrl(1, sprintf('%sreleases/%d', self::DISCOGS_API_URL, 1));

        self::assertSame(200, $statusCode);
    }
}