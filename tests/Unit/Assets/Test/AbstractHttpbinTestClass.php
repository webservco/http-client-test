<?php

declare(strict_types=1);

namespace Tests\Unit\Assets\Test;

use Psr\Http\Message\RequestInterface;
use Tests\Unit\Assets\Factory\CurlServiceFactory;
use Tests\Unit\Assets\Factory\Request\Httpbin\HttpbinRequestFactory;
use WebServCo\Http\Client\Service\PSR18\HttpClient;

use function realpath;

use const DIRECTORY_SEPARATOR;

abstract class AbstractHttpbinTestClass extends AbstractTestClass
{
    protected const string BASE_URL = 'http://0.0.0.0:8080/';

    protected function createGetRequest(string $url): RequestInterface
    {
        $requestFactory = new HttpbinRequestFactory();

        return $requestFactory->createGetRequest($url);
    }

    protected function createHttpClient(int $timeout): HttpClient
    {
        $projectPath = realpath(__DIR__ . '/../../../../') . DIRECTORY_SEPARATOR;

        $curlServiceFactory = new CurlServiceFactory($projectPath);
        $curlService = $curlServiceFactory->createCurlService($timeout);

        return new HttpClient($curlService);
    }
}
