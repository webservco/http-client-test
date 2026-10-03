<?php

declare(strict_types=1);

namespace Tests\Unit\Assets\Factory\Request\Httpbin;

use Override;
use Psr\Http\Message\RequestInterface;
use Tests\Unit\Assets\Factory\Request\AbstractRequestFactory;

final class HttpbinRequestFactory extends AbstractRequestFactory
{
    #[Override]
    protected function addRequestHeaders(RequestInterface $request): RequestInterface
    {
        return $request
            ->withHeader('Accept', 'application/json')
            // Leave empty string, cURL will list all supported
            ->withHeader('Accept-Encoding', '');
    }
}
