<?php

declare(strict_types=1);

namespace Tests\Unit\Assets\Test;

use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Tests\Unit\Assets\Factory\CurlServiceFactory;
use WebServCo\Http\Client\Service\PSR18\HttpClient;

use function realpath;

use const DIRECTORY_SEPARATOR;

abstract class AbstractTestClass extends TestCase
{
    abstract protected function createGetRequest(string $url): RequestInterface;

    protected function createHttpClient(int $timeout): HttpClient
    {
        $projectPath = realpath(__DIR__ . '/../../../../') . DIRECTORY_SEPARATOR;

        $curlServiceFactory = new CurlServiceFactory($projectPath);
        $curlService = $curlServiceFactory->createCurlService($timeout);

        return new HttpClient($curlService);
    }

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
            return $exception->getCode();
        }
    }
}
