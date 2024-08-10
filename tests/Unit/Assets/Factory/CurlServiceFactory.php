<?php

declare(strict_types=1);

namespace Tests\Unit\Assets\Factory;

use WebServCo\Http\Client\Contract\Service\cURL\CurlServiceInterface;
use WebServCo\Http\Client\DataTransfer\CurlServiceConfiguration;
use WebServCo\Http\Client\Service\cURL\CurlService;
use WebServCo\Http\Factory\Message\Response\ResponseFactory;
use WebServCo\Http\Factory\Message\Stream\StreamFactory;
use WebServCo\Http\Service\Message\Response\StatusCodeService;
use WebServCo\Log\Contract\LoggerFactoryInterface;

final class CurlServiceFactory
{
    public function __construct(private LoggerFactoryInterface $loggerFactory)
    {
    }

    public function createCurlService(int $timeout): CurlServiceInterface
    {
        $streamFactory = new StreamFactory();

        return new CurlService(
            new CurlServiceConfiguration(true, $timeout),
            $this->loggerFactory,
            new ResponseFactory(new StatusCodeService(), $streamFactory),
            $streamFactory,
        );
    }
}
