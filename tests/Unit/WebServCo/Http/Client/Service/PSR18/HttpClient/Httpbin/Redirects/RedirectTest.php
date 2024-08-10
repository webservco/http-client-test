<?php

declare(strict_types=1);

namespace Tests\Unit\WebServCo\Http\Client\Service\PSR18\HttpClient\Httpbin\Redirects;

use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Http\Client\ClientExceptionInterface;
use Tests\Unit\Assets\Test\AbstractHttpbinTestClass;
use WebServCo\Http\Client\Service\PSR18\HttpClient;

use function sprintf;

#[CoversClass(HttpClient::class)]
final class RedirectTest extends AbstractHttpbinTestClass
{
    /**
     * Test that only the last headers are kept when redirects are involved.
     */
    public function testRedirectContentType(): void
    {
        try {
            $response = $this->getGetResponse(1, sprintf('%sredirect/%d', self::BASE_URL, 3));
            $responseContentType = $response->getHeaderLine('content-type');
        } catch (ClientExceptionInterface) {
            $responseContentType = null;
        }

        self::assertEquals('application/json', $responseContentType);
    }

    public function testRedirectStatusCode(): void
    {
        $statusCode = $this->getGetResponseStatusCodeByUrl(1, sprintf('%sredirect/%d', self::BASE_URL, 3));

        self::assertSame(200, $statusCode);
    }
}
