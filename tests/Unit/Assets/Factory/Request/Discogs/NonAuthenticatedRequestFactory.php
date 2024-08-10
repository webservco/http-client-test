<?php

declare(strict_types=1);

namespace Tests\Unit\Assets\Factory\Request\Discogs;

use Psr\Http\Message\RequestInterface;
use Tests\Unit\Assets\Factory\Request\AbstractRequestFactory;

final class NonAuthenticatedRequestFactory extends AbstractRequestFactory
{
    protected function addRequestHeaders(RequestInterface $request): RequestInterface
    {
        return $request
            ->withHeader('Accept', 'application/vnd.discogs.v2.discogs+json')
            // Leave empty string, cURL will list all supported
            ->withHeader('Accept-Encoding', '')
            ->withHeader('User-Agent', 'WebServCo HttpClient/0.1 +https://github.com/webservco/http-client');
    }
}
