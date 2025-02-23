<?php

declare(strict_types=1);

namespace Tests\Unit\Assets\Test\Discogs;

use Psr\Http\Message\RequestInterface;
use Tests\Unit\Assets\Factory\Request\Discogs\AuthenticatedRequestFactory;
use Tests\Unit\Assets\Test\AbstractTestClass;
use WebServCo\Configuration\Contract\ConfigurationGetterInterface;
use WebServCo\Configuration\Factory\ServerConfigurationGetterFactory;
use WebServCo\Configuration\Service\ConfigurationFileProcessor;
use WebServCo\Configuration\Service\IniServerConfigurationContainer;

use function assert;
use function rtrim;

use const DIRECTORY_SEPARATOR;

abstract class AbstractDiscogsTestClass extends AbstractTestClass
{
    protected const array RELEASE_IDS_11 = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11];

    protected ?ConfigurationGetterInterface $configurationGetter = null;

    private ?AuthenticatedRequestFactory $requestFactory = null;

    protected function createGetRequest(string $url): RequestInterface
    {
        assert($this->requestFactory instanceof AuthenticatedRequestFactory);

        return $this->requestFactory->createGetRequest($url);
    }

    protected function getDiscogsApiUrl(): string
    {
        assert($this->configurationGetter instanceof ConfigurationGetterInterface);

        $apiUrl = $this->configurationGetter->getString('DISCOGS_API_URL');

        // Make sure path contains trailing slash (trim + add back).
        return rtrim($apiUrl, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    /**
     * @return array<int,int>
     */
    protected function getReleaseIds(int $numberOfItems): array
    {
        $data = [];
        for ($releaseId = 1; $releaseId <= $numberOfItems; $releaseId += 1) {
            $data[] = $releaseId;
        }

        return $data;
    }

    protected function setUp(): void
    {
        $projectPath = $this->getProjectPath();

        // Configuration (set).
        $configurationContainer = new IniServerConfigurationContainer();
        $configurationFileProcessor = new ConfigurationFileProcessor(
            $configurationContainer->getConfigurationDataProcessor(),
            $configurationContainer->getConfigurationLoader(),
            $configurationContainer->getConfigurationSetter(),
        );
        $configurationFileProcessor->processConfigurationFile($projectPath, 'config', '.env.ini');

        // Configuration (get).
        $configurationGetterFactory = new ServerConfigurationGetterFactory();
        $this->configurationGetter = $configurationGetterFactory->createConfigurationGetter();

        $this->requestFactory = new AuthenticatedRequestFactory($this->configurationGetter);
    }

    protected function tearDown(): void
    {
        $this->requestFactory = null;
    }
}
