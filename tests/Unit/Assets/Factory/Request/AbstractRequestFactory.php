<?php

declare(strict_types=1);

namespace Tests\Unit\Assets\Factory\Request;

use Fig\Http\Message\RequestMethodInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use WebServCo\Http\Factory\Message\Request\RequestFactory;
use WebServCo\Http\Factory\Message\Stream\StreamFactory;
use WebServCo\Http\Factory\Message\UriFactory;
use WebServCo\Http\Service\Message\Request\Method\RequestMethodService;

abstract class AbstractRequestFactory
{
    protected RequestFactoryInterface $requestFactory;

    abstract protected function addRequestHeaders(RequestInterface $request): RequestInterface;

    public function __construct()
    {
        $this->requestFactory = new RequestFactory(
            new RequestMethodService(),
            new StreamFactory(),
            new UriFactory(),
        );
    }

    public function createGetRequest(string $url): RequestInterface
    {
        $request = $this->requestFactory->createRequest(RequestMethodInterface::METHOD_GET, $url);

        return $this->addRequestHeaders($request);
    }
}
