<?php

declare(strict_types=1);

namespace Tests\Unit\Assets\Factory\Request\Discogs;

use Psr\Http\Message\RequestInterface;
use Tests\Unit\Assets\Factory\Request\AbstractRequestFactory;
use WebServCo\Configuration\Contract\ConfigurationGetterInterface;

use function sprintf;

final class AuthenticatedRequestFactory extends AbstractRequestFactory
{
    public function __construct(private ConfigurationGetterInterface $configurationGetter)
    {
        parent::__construct();
    }

    protected function addRequestHeaders(RequestInterface $request): RequestInterface
    {
        return $request
            ->withHeader('Accept', 'application/vnd.discogs.v2.discogs+json')
            // Leave empty string, cURL will list all supported
            ->withHeader('Accept-Encoding', '')
            ->withHeader(
                'Authorization',
                sprintf(
                    'Discogs token=%s',
                    $this->configurationGetter->getString('DISCOGS_API_AUTHORIZATION_TOKEN'),
                ),
            )
            ->withHeader('User-Agent', $this->configurationGetter->getString('DISCOGS_API_USER_AGENT'));
    }
}
