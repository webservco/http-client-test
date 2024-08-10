<?php

declare(strict_types=1);

namespace Tests\Unit\Assets\Test\Discogs;

use Psr\Http\Message\RequestInterface;
use Tests\Unit\Assets\Factory\Request\Discogs\NonAuthenticatedRequestFactory;
use Tests\Unit\Assets\Test\AbstractTestClass;

abstract class AbstractDiscogsTestClass extends AbstractTestClass
{
    protected const string DISCOGS_API_URL = 'https://api.discogs.com/';

    protected const array RELEASE_IDS = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11];

    protected function createGetRequest(string $url): RequestInterface
    {
        $requestFactory = new NonAuthenticatedRequestFactory();

        return $requestFactory->createGetRequest($url);
    }
}
