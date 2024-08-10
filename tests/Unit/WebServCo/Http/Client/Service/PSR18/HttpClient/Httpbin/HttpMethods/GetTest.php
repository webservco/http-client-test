<?php

declare(strict_types=1);

namespace Tests\Unit\WebServCo\Http\Client\Service\PSR18\HttpClient\Httpbin\HttpMethods;

use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Unit\Assets\Test\Httpbin\AbstractHttpbinTestClass;
use WebServCo\Http\Client\Service\PSR18\HttpClient;

use function sprintf;

#[CoversClass(HttpClient::class)]
final class GetTest extends AbstractHttpbinTestClass
{
    public function testGetStatusCode(): void
    {
        $statusCode = $this->getGetResponseStatusCodeByUrl(1, sprintf('%sget', self::BASE_URL));

        self::assertSame(200, $statusCode);
    }
}
