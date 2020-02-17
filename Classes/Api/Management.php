<?php
declare(strict_types = 1);
namespace Bitmotion\Auth0\Api;

/***
 *
 * This file is part of the "Auth0 for TYPO3" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2018 Florian Wessels <f.wessels@bitmotion.de>, Bitmotion GmbH
 *
 ***/

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
use Bitmotion\Auth0\Exception\InvalidApplicationException;
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
     * @throws InvalidApplicationException
     * @throws Exception
     */
    public function __construct(int $applicationUid = 0, string $scope = null, array $guzzleOptions = [])
    {
        $this->application = GeneralUtility::makeInstance(ApplicationRepository::class)->findByUid($applicationUid, true);
        $this->setAuthentication($scope);
        $this->setClient(array_merge($this->guzzleOptions, $guzzleOptions));
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

    public function getClientGrantApi(): ClientGrantApi
    {
        return $this->clientGrantApi ?? GeneralUtility::makeInstance(ClientGrantApi::class, $this->client);
    }

    public function getClientApi(): ClientApi
    {
        return $this->clientApi ?? GeneralUtility::makeInstance(ClientApi::class, $this->client);
    }

    public function getConnectionApi(): ConnectionApi
    {
        return $this->connectionApi ?? GeneralUtility::makeInstance(ConnectionApi::class, $this->client);
    }

    public function getCustomDomainApi(): CustomDomainApi
    {
        return $this->customDomainApi ?? GeneralUtility::makeInstance(CustomDomainApi::class, $this->client);
    }

    public function getDeviceCredentialApi(): DeviceCredentialApi
    {
        return $this->deviceCredentialApi ?? GeneralUtility::makeInstance(DeviceCredentialApi::class, $this->client);
    }

    public function getGrantApi(): GrantApi
    {
        return $this->grantApi ?? GeneralUtility::makeInstance(GrantApi::class, $this->client);
    }

    public function getLogApi(): LogApi
    {
        return $this->logApi ?? GeneralUtility::makeInstance(LogApi::class, $this->client);
    }

    public function getResourceServerApi(): ResourceServerApi
    {
        return $this->resourceServerApi ?? GeneralUtility::makeInstance(ResourceServerApi::class, $this->client);
    }

    public function getRuleApi(): RuleApi
    {
        return $this->ruleApi ?? GeneralUtility::makeInstance(RuleApi::class, $this->client);
    }

    public function getRuleConfigApi(): RuleConfigApi
    {
        return $this->ruleConfigApi ?? GeneralUtility::makeInstance(RuleConfigApi::class, $this->client);
    }

    public function getUserBlockApi(): UserBlockApi
    {
        return $this->userBlockApi ?? GeneralUtility::makeInstance(UserBlockApi::class, $this->client);
    }

    public function getUserApi(): UserApi
    {
        return $this->userApi ?? GeneralUtility::makeInstance(UserApi::class, $this->client);
    }

    public function getUserByEmailApi(): UserByEmailApi
    {
        return $this->userByEmailApi ?? GeneralUtility::makeInstance(UserByEmailApi::class, $this->client);
    }

    public function getBlacklistApi(): BlacklistApi
    {
        return $this->blacklistApi ?? GeneralUtility::makeInstance(BlacklistApi::class, $this->client);
    }

    public function getEmailTemplateApi(): EmailTemplateApi
    {
        return $this->emailTemplateApi ?? GeneralUtility::makeInstance(EmailTemplateApi::class, $this->client);
    }

    public function getEmailApi(): EmailApi
    {
        return $this->emailApi ?? GeneralUtility::makeInstance(EmailApi::class, $this->client);
    }

    public function getGuardianApi(): GuardianApi
    {
        return $this->guardianApi ?? GeneralUtility::makeInstance(GuardianApi::class, $this->client);
    }

    public function getJobApi(): JobApi
    {
        return $this->jobApi ?? GeneralUtility::makeInstance(JobApi::class, $this->client);
    }

    public function getStatApi(): StatApi
    {
        return $this->statApi ?? GeneralUtility::makeInstance(StatApi::class, $this->client);
    }

    public function getTenantApi(): TenantApi
    {
        return $this->tenantApi ?? GeneralUtility::makeInstance(TenantApi::class, $this->client);
    }

    public function getTicketApi(): TicketApi
    {
        return $this->ticketApi ?? GeneralUtility::makeInstance(TicketApi::class, $this->client);
    }
}
