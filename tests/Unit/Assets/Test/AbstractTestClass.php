<?php

declare(strict_types=1);

namespace Tests\Unit\Assets\Test;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use WebServCo\Http\Client\Contract\Service\cURL\CurlMultiServiceInterface;

abstract class AbstractTestClass extends AbstractFactoryTestClass
{
    abstract protected function createGetRequest(string $url): RequestInterface;

    protected function getGetResponse(int $timeout, string $url): ResponseInterface
    {
        $httpClient = $this->createHttpClient($timeout);

        $request = $this->createGetRequest($url);

        return $httpClient->sendRequest($request);
    }

    protected function getGetResponseStatusCodeByUrl(int $timeout, string $url): int
    {
        try {
            $response = $this->getGetResponse($timeout, $url);

            return $response->getStatusCode();
        } catch (ClientExceptionInterface $exception) {
            /**
             * Psalm error "Redundant cast to int"
             *
             * @psalm-suppress RedundantCast
             */
            return (int) $exception->getCode();
        }
    }

    protected function getMultiResponseStatusCode(
        CurlMultiServiceInterface $curlMultiService,
        string $handleIdentifier,
    ): int {
        try {
            $response = $curlMultiService->getResponse($handleIdentifier);

            return $response->getStatusCode();
        } catch (ClientExceptionInterface $exception) {
            /**
             * Psalm error "Redundant cast to int"
             *
             * @psalm-suppress RedundantCast
             */
            return (int) $exception->getCode();
        }
    }
}
