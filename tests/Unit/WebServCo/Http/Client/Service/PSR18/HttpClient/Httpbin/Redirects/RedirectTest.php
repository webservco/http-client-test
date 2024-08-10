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
    public function testRedirect(): void
    {
        $httpClient = $this->createHttpClient(1);
        $request = $this->createGetRequest(sprintf('%sredirect/%d', self::BASE_URL, 3));

        try {
            $response = $httpClient->sendRequest($request);
            $statusCode = $response->getStatusCode();
            $responseContentType = $response->getHeaderLine('content-type');
        } catch (ClientExceptionInterface $exception) {
            $statusCode = $exception->getCode();
            $responseContentType = null;
        }

        self::assertSame(200, $statusCode);
        // Test that only the last headers are kept when redirects are involved.
        self::assertEquals('application/json', $responseContentType);
    }
}
