<?php

declare(strict_types=1);

namespace Tests\Unit\Assets\Test;

use Psr\Http\Message\RequestInterface;
use Tests\Unit\Assets\Factory\Request\Httpbin\HttpbinRequestFactory;

abstract class AbstractHttpbinTestClass extends AbstractTestClass
{
    protected const string BASE_URL = 'http://0.0.0.0:8080/';

    protected function createGetRequest(string $url): RequestInterface
    {
        $requestFactory = new HttpbinRequestFactory();

        return $requestFactory->createGetRequest($url);
    }
}
