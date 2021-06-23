<?php

declare(strict_types=1);

/*
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

namespace Bitmotion\Auth0\Api;

use Auth0\SDK\API\Header\AuthorizationBearer;
use Auth0\SDK\Exception\ApiException;
use Bitmotion\Auth0\Api\Management\GeneralManagementApi;
use Bitmotion\Auth0\Domain\Model\Auth0\Api\Client;
use Bitmotion\Auth0\Domain\Repository\ApplicationRepository;
use Bitmotion\Auth0\Exception\ApiNotEnabledException;
use Bitmotion\Auth0\Exception\IllegalClassNameException;
use Bitmotion\Auth0\Exception\InvalidApplicationException;
use Bitmotion\Auth0\Exception\UnknownPropertyException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Package\Exception;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Management implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var Authentication
     */
    protected $authentication;

    /**
     * @var array
     */
    protected $application;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var array
     */
    protected $guzzleOptions = [
        'http_errors' => false,
    ];

    protected $clientGrantApi;

    protected $clientApi;

    protected $connectionApi;

    protected $customDomainApi;

    protected $deviceCredentialApi;

    protected $grantApi;

    protected $logApi;

    protected $resourceServerApi;

    protected $ruleApi;

    protected $ruleConfigApi;

    protected $userBlockApi;

    protected $userApi;

    protected $userByEmailApi;

    protected $blacklistApi;

    protected $emailTemplateApi;

    protected $emailApi;

    protected $guardianApi;

    protected $jobApi;

    protected $statApi;

    protected $tenantApi;

    protected $ticketApi;

    /**
     * @throws ApiException
     * @throws ApiNotEnabledException
     * @throws InvalidApplicationException
     * @throws Exception
     */
    public function __construct(int $applicationUid = 0, string $scope = null, array $guzzleOptions = [])
    {
        $application = GeneralUtility::makeInstance(ApplicationRepository::class)->findByUid($applicationUid);

        if ($application->hasApi() === false) {
            throw new ApiNotEnabledException(
                sprintf('Using the API is not enabled for Auth0 application "%s"', $application->getTitle()),
                1606121212
            );
        }

        $this->application = $application;
        $this->setAuthentication($scope);
        $this->setClient(array_merge($this->guzzleOptions, $guzzleOptions));
    }

    public function getApi(string $className): GeneralManagementApi
    {
        if (strpos($className, 'Bitmotion\\Auth0\\Api\\Management') !== 0) {
            throw new IllegalClassNameException(sprintf('It is not allowed to instantiate class %s.', $className), 1605878552);
        }

        $apiClass = $this->extractApiClass($className);

        if (!property_exists($this, $apiClass)) {
            throw new UnknownPropertyException(sprintf('Class %s has no property %s', self::class, $apiClass), 1605878741);
        }

        return $this->$apiClass ?? ($this->$apiClass = GeneralUtility::makeInstance($className, $this->client));
    }

    /**
     * @throws ApiException
     * @throws Exception
     */
    protected function setClient(array $guzzleOptions): void
    {
        $clientCredentials = $this->getClientCredentials();

        $this->client = new Client();
        $this->client->setDomain($this->application->getFullDomain());
        $this->client->setBasePath($this->application->getApiBasePath());
        $this->client->setGuzzleOptions($guzzleOptions);
        $this->client->addHeader(new AuthorizationBearer($clientCredentials['access_token']));
    }

    protected function setAuthentication(string $scope): void
    {
        $this->authentication = new Authentication(
            $this->application->getDomain(),
            $this->application->getClientId(),
            $this->application->getClientSecret(),
            $this->application->getAudience(true),
            $scope
        );
    }

    /**
     * @throws ApiException
     */
    protected function getClientCredentials(): array
    {
        $clientCredentials = $this->authentication->client_credentials([
            'client_secret' => $this->application->getClientSecret(),
            'client_id' => $this->application->getClientId(),
            'audience' => $this->application->getAudience(true),
        ]);

        return $clientCredentials ?: [];
    }

    protected function extractApiClass(string $className): string
    {
        $segments = explode('\\', $className);

        return lcfirst(array_pop($segments));
    }
}
