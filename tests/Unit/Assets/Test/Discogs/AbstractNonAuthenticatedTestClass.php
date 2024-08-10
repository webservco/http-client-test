<?php

declare(strict_types=1);

namespace Tests\Unit\Assets\Test\Discogs;

use Psr\Http\Message\RequestInterface;
use Tests\Unit\Assets\Factory\Request\Discogs\NonAuthenticatedRequestFactory;
use Tests\Unit\Assets\Test\AbstractTestClass;

abstract class AbstractNonAuthenticatedTestClass extends AbstractTestClass
{
    protected const string DISCOGS_API_URL = 'https://api.discogs.com/';

    protected function createGetRequest(string $url): RequestInterface
    {
        $requestFactory = new NonAuthenticatedRequestFactory();

        return $requestFactory->createGetRequest($url);
    }
}
