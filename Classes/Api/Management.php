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
use Bitmotion\Auth0\Api\Management\BlacklistApi;
use Bitmotion\Auth0\Api\Management\ClientApi;
use Bitmotion\Auth0\Api\Management\ClientGrantApi;
use Bitmotion\Auth0\Api\Management\ConnectionApi;
use Bitmotion\Auth0\Api\Management\CustomDomainApi;
use Bitmotion\Auth0\Api\Management\DeviceCredentialApi;
use Bitmotion\Auth0\Api\Management\EmailApi;
use Bitmotion\Auth0\Api\Management\EmailTemplateApi;
use Bitmotion\Auth0\Api\Management\GeneralManagementApi;
use Bitmotion\Auth0\Api\Management\GrantApi;
use Bitmotion\Auth0\Api\Management\GuardianApi;
use Bitmotion\Auth0\Api\Management\JobApi;
use Bitmotion\Auth0\Api\Management\LogApi;
use Bitmotion\Auth0\Api\Management\ResourceServerApi;
use Bitmotion\Auth0\Api\Management\RuleApi;
use Bitmotion\Auth0\Api\Management\RuleConfigApi;
use Bitmotion\Auth0\Api\Management\StatApi;
use Bitmotion\Auth0\Api\Management\TenantApi;
use Bitmotion\Auth0\Api\Management\TicketApi;
use Bitmotion\Auth0\Api\Management\UserApi;
use Bitmotion\Auth0\Api\Management\UserBlockApi;
use Bitmotion\Auth0\Api\Management\UserByEmailApi;
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
        $application = GeneralUtility::makeInstance(ApplicationRepository::class)->findByUid($applicationUid, true);

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

    /**
     * @deprecated Will be removed in version 4. Please use $this->getApi() instead.
     */
    public function getClientGrantApi(): ClientGrantApi
    {
        trigger_error('getClientGrantApi() is deprecated. Please use $this->getApi() instead.', E_USER_DEPRECATED);

        return $this->getApi(ClientGrantApi::class);
    }

    /**
     * @deprecated Will be removed in version 4. Please use $this->getApi() instead.
     */
    public function getClientApi(): ClientApi
    {
        trigger_error('getClientApi() is deprecated. Please use $this->getApi() instead.', E_USER_DEPRECATED);

        return $this->getApi(ClientApi::class);
    }

    /**
     * @deprecated Will be removed in version 4. Please use $this->getApi() instead.
     */
    public function getConnectionApi(): ConnectionApi
    {
        trigger_error('getConnectionApi() is deprecated. Please use $this->getApi() instead.', E_USER_DEPRECATED);

        return $this->getApi(ConnectionApi::class);
    }

    /**
     * @deprecated Will be removed in version 4. Please use $this->getApi() instead.
     */
    public function getCustomDomainApi(): CustomDomainApi
    {
        trigger_error('getCustomDomainApi() is deprecated. Please use $this->getApi() instead.', E_USER_DEPRECATED);

        return $this->getApi(CustomDomainApi::class);
    }

    /**
     * @deprecated Will be removed in version 4. Please use $this->getApi() instead.
     */
    public function getDeviceCredentialApi(): DeviceCredentialApi
    {
        trigger_error('getDeviceCredentialApi() is deprecated. Please use $this->getApi() instead.', E_USER_DEPRECATED);

        return $this->getApi(DeviceCredentialApi::class);
    }

    /**
     * @deprecated Will be removed in version 4. Please use $this->getApi() instead.
     */
    public function getGrantApi(): GrantApi
    {
        trigger_error('getGrantApi() is deprecated. Please use $this->getApi() instead.', E_USER_DEPRECATED);

        return $this->getApi(GrantApi::class);
    }

    /**
     * @deprecated Will be removed in version 4. Please use $this->getApi() instead.
     */
    public function getLogApi(): LogApi
    {
        trigger_error('getLogApi() is deprecated. Please use $this->getApi() instead.', E_USER_DEPRECATED);

        return $this->getApi(LogApi::class);
    }

    /**
     * @deprecated Will be removed in version 4. Please use $this->getApi() instead.
     */
    public function getResourceServerApi(): ResourceServerApi
    {
        trigger_error('getResourceServerApi() is deprecated. Please use $this->getApi() instead.', E_USER_DEPRECATED);

        return $this->getApi(ResourceServerApi::class);
    }

    /**
     * @deprecated Will be removed in version 4. Please use $this->getApi() instead.
     */
    public function getRuleApi(): RuleApi
    {
        trigger_error('getRuleApi() is deprecated. Please use $this->getApi() instead.', E_USER_DEPRECATED);

        return $this->getApi(RuleApi::class);
    }

    /**
     * @deprecated Will be removed in version 4. Please use $this->getApi() instead.
     */
    public function getRuleConfigApi(): RuleConfigApi
    {
        trigger_error('getRuleConfigApi() is deprecated. Please use $this->getApi() instead.', E_USER_DEPRECATED);

        return $this->getApi(RuleConfigApi::class);
    }

    /**
     * @deprecated Will be removed in version 4. Please use $this->getApi() instead.
     */
    public function getUserBlockApi(): UserBlockApi
    {
        trigger_error('getUserBlockApi() is deprecated. Please use $this->getApi() instead.', E_USER_DEPRECATED);

        return $this->getApi(UserBlockApi::class);
    }

    /**
     * @deprecated Will be removed in version 4. Please use $this->getApi() instead.
     */
    public function getUserApi(): UserApi
    {
        trigger_error('getUserApi() is deprecated. Please use $this->getApi() instead.', E_USER_DEPRECATED);

        return $this->getApi(UserApi::class);
    }

    /**
     * @deprecated Will be removed in version 4. Please use $this->getApi() instead.
     */
    public function getUserByEmailApi(): UserByEmailApi
    {
        trigger_error('getUserByEmailApi() is deprecated. Please use $this->getApi() instead.', E_USER_DEPRECATED);

        return $this->getApi(UserByEmailApi::class);
    }

    /**
     * @deprecated Will be removed in version 4. Please use $this->getApi() instead.
     */
    public function getBlacklistApi(): BlacklistApi
    {
        trigger_error('getBlacklistApi() is deprecated. Please use $this->getApi() instead.', E_USER_DEPRECATED);

        return $this->getApi(BlacklistApi::class);
    }

    /**
     * @deprecated Will be removed in version 4. Please use $this->getApi() instead.
     */
    public function getEmailTemplateApi(): EmailTemplateApi
    {
        trigger_error('getEmailTemplateApi() is deprecated. Please use $this->getApi() instead.', E_USER_DEPRECATED);

        return $this->getApi(EmailTemplateApi::class);
    }

    /**
     * @deprecated Will be removed in version 4. Please use $this->getApi() instead.
     */
    public function getEmailApi(): EmailApi
    {
        trigger_error('getEmailApi() is deprecated. Please use $this->getApi() instead.', E_USER_DEPRECATED);

        return $this->getApi(EmailApi::class);
    }

    /**
     * @deprecated Will be removed in version 4. Please use $this->getApi() instead.
     */
    public function getGuardianApi(): GuardianApi
    {
        trigger_error('getGuardianApi() is deprecated. Please use $this->getApi() instead.', E_USER_DEPRECATED);

        return $this->getApi(GuardianApi::class);
    }

    /**
     * @deprecated Will be removed in version 4. Please use $this->getApi() instead.
     */
    public function getJobApi(): JobApi
    {
        trigger_error('getJobApi() is deprecated. Please use $this->getApi() instead.', E_USER_DEPRECATED);

        return $this->getApi(JobApi::class);
    }

    /**
     * @deprecated Will be removed in version 4. Please use $this->getApi() instead.
     */
    public function getStatApi(): StatApi
    {
        trigger_error('getStatApi() is deprecated. Please use $this->getApi() instead.', E_USER_DEPRECATED);

        return $this->getApi(ClientGrantApi::class);
    }

    /**
     * @deprecated Will be removed in version 4. Please use $this->getApi() instead.
     */
    public function getTenantApi(): TenantApi
    {
        trigger_error('getTenantApi() is deprecated. Please use $this->getApi() instead.', E_USER_DEPRECATED);

        return $this->getApi(TenantApi::class);
    }

    /**
     * @deprecated Will be removed in version 4. Please use $this->getApi() instead.
     */
    public function getTicketApi(): TicketApi
    {
        trigger_error('getTicketApi() is deprecated. Please use $this->getApi() instead.', E_USER_DEPRECATED);

        return $this->getApi(TicketApi::class);
    }
}
