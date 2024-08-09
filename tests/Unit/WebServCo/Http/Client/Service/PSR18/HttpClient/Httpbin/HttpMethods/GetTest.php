<?php

declare(strict_types=1);

namespace Tests\Unit\WebServCo\Http\Client\Service\PSR18\HttpClient\Httpbin\HttpMethods;

use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Http\Client\ClientExceptionInterface;
use Tests\Unit\Assets\Test\AbstractHttpbinTestClass;
use WebServCo\Http\Client\Service\PSR18\HttpClient;

use function sprintf;

#[CoversClass(HttpClient::class)]
final class GetTest extends AbstractHttpbinTestClass
{
    public function testBasic(): void
    {
        $httpClient = $this->createHttpClient(1);
        $request = $this->createGetRequest(sprintf('%sget', self::BASE_URL));

        try {
            $response = $httpClient->sendRequest($request);
            $statusCode = $response->getStatusCode();
            $responseContentType = $response->getHeaderLine('content-type');
        } catch (ClientExceptionInterface $exception) {
            $statusCode = $exception->getCode();
            $responseContentType = null;
        }

        self::assertSame(200, $statusCode);
        self::assertEquals('application/json', $responseContentType);
    }
}
