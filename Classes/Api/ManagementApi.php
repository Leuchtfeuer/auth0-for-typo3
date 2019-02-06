<?php
declare(strict_types=1);
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

use Auth0\SDK\API\Authentication;
use Auth0\SDK\API\Management;
use Auth0\SDK\API\Management\Blacklists;
use Auth0\SDK\API\Management\ClientGrants;
use Auth0\SDK\API\Management\Clients;
use Auth0\SDK\API\Management\DeviceCredentials;
use Auth0\SDK\API\Management\Emails;
use Auth0\SDK\API\Management\EmailTemplates;
use Auth0\SDK\API\Management\Jobs;
use Auth0\SDK\API\Management\Logs;
use Auth0\SDK\API\Management\ResourceServers;
use Auth0\SDK\API\Management\Rules;
use Auth0\SDK\API\Management\Stats;
use Auth0\SDK\API\Management\Tenants;
use Auth0\SDK\API\Management\UserBlocks;
use Auth0\SDK\API\Management\Users;
use Auth0\SDK\Exception\ApiException;
use Bitmotion\Auth0\Api\Management\BlacklistApi;
use Bitmotion\Auth0\Api\Management\ConnectionApi;
use Bitmotion\Auth0\Api\Management\EmailApi;
use Bitmotion\Auth0\Api\Management\EmailTemplateApi;
use Bitmotion\Auth0\Api\Management\GuardianApi;
use Bitmotion\Auth0\Api\Management\JobApi;
use Bitmotion\Auth0\Api\Management\StatApi;
use Bitmotion\Auth0\Api\Management\TenantApi;
use Bitmotion\Auth0\Api\Management\TicketApi;
use Bitmotion\Auth0\Api\Management\UserApi;
use Bitmotion\Auth0\Api\Management\UserBlockApi;
use Bitmotion\Auth0\Api\Management\UserByEmailApi;
use Bitmotion\Auth0\Domain\Repository\ApplicationRepository;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ManagementApi extends Management implements SingletonInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var Authentication
     */
    protected $authenticationApi;

    /**
     * @var array
     */
    protected $application;

    protected $connectionApi = null;

    protected $userBlockApi = null;

    protected $userApi = null;

    protected $userByEmailApi = null;

    protected $blacklistApi = null;

    protected $emailTemplateApi = null;

    protected $emailApi = null;

    protected $guardianApi = null;

    protected $jobApi = null;

    protected $statApi = null;

    protected $tenantApi = null;

    protected $ticketApi = null;

    /**
     * @deprecated
     */
    public $connections;

    /**
     * @deprecated
     */
    public $tickets;

    /**
     * @deprecated
     */
    public $blacklists;

    /**
     * @deprecated
     */
    public $clients;

    /**
     * @deprecated
     */
    public $client_grants;

    /**
     * @deprecated
     */
    public $deviceCredentials;

    /**
     * @deprecated
     */
    public $users;

    /**
     * @deprecated
     */
    public $emails;

    /**
     * @deprecated
     */
    public $emailTemplates;

    /**
     * @deprecated
     */
    public $jobs;

    /**
     * @deprecated
     */
    public $logs;

    /**
     * @deprecated
     */
    public $rules;

    /**
     * @deprecated
     */
    public $resource_servers;

    /**
     * @deprecated
     */
    public $stats;

    /**
     * @deprecated
     */
    public $tenants;

    /**
     * @deprecated
     */
    public $userBlocks;

    /**
     * @deprecated
     */
    public $usersByEmail;

    /**
     * @throws ApiException
     * @throws \Bitmotion\Auth0\Exception\InvalidApplicationException
     */
    public function __construct(int $applicationUid = 0, string $scope = null)
    {
        if (!$this->authenticationApi instanceof Authentication) {
            $authentication = $this->getAuthentication($applicationUid, $scope);
            $credentials = $this->connect($authentication);

            parent::__construct(
                $credentials['access_token'],
                $this->application['domain'],
                [
                    'http_errors' => false,
                ]
            );
        }
    }

    /**
     * @throws \Bitmotion\Auth0\Exception\InvalidApplicationException
     */
    protected function getAuthentication(int $applicationUid, $scope): Authentication
    {
        $applicationRepository = GeneralUtility::makeInstance(ApplicationRepository::class);
        $this->application = $applicationRepository->findByUid($applicationUid);

        return new Authentication(
            $this->application['domain'],
            $this->application['id'],
            $this->application['secret'],
            "https://{$this->application['domain']}/{$this->application['audience']}",
            $scope
        );
    }

    /**
     * @throws ApiException
     */
    protected function connect(Authentication $authentication): array
    {
        $result = $authentication->client_credentials([
            'client_secret' => $this->application['secret'],
            'client_id' => $this->application['id'],
            'audience' => 'https://' . $this->application['domain'] . '/' . $this->application['audience'],
        ]);

        $this->authenticationApi = $authentication;

        return $result ?: [];
    }

    /**
     * @throws \Exception
     */
    public function getUserById(string $userId)
    {
        return $this->users->get($userId);
    }

    public function getClientApi(): Clients
    {
        return $this->clients;
    }

    public function getClientGrantApi(): ClientGrants
    {
        return $this->client_grants;
    }

    public function getConnectionApi(): ConnectionApi
    {
        return $this->connectionApi ?? GeneralUtility::makeInstance(ConnectionApi::class, $this->connections->getApiClient());
    }

    public function getDeviceCredentialApi(): DeviceCredentials
    {
        return $this->deviceCredentials;
    }

    public function getLogApi(): Logs
    {
        return $this->logs;
    }

    public function getRuleApi(): Rules
    {
        return $this->rules;
    }

    public function getResourceServerApi(): ResourceServers
    {
        return $this->resource_servers;
    }

    public function getUserBlockApi(): UserBlockApi
    {
        return $this->userBlockApi ?? GeneralUtility::makeInstance(UserBlockApi::class, $this->userBlocks->getApiClient());
    }

    public function getUserApi(): UserApi
    {
        return $this->userApi ?? GeneralUtility::makeInstance(UserApi::class, $this->users->getApiClient());
    }

    public function getUserByEmailApi(): UserByEmailApi
    {
        return $this->userByEmailApi ?? GeneralUtility::makeInstance(UserByEmailApi::class, $this->usersByEmail->getApiClient());
    }

    public function getBlacklistApi(): BlacklistApi
    {
        return $this->blacklistApi ?? GeneralUtility::makeInstance(BlacklistApi::class, $this->blacklists->getApiClient());
    }

    public function getEmailTemplateApi(): EmailTemplateApi
    {
        return $this->emailTemplateApi ?? GeneralUtility::makeInstance(EmailTemplateApi::class, $this->emailTemplates->getApiClient());
    }

    public function getEmailApi(): EmailApi
    {
        return $this->emailApi ?? GeneralUtility::makeInstance(EmailApi::class, $this->emails->getApiClient());
    }

    public function getGuardianApi(): GuardianApi
    {
        return $this->guardianApi ?? GeneralUtility::makeInstance(GuardianApi::class, $this->jobs->getApiClient());
    }

    public function getJobApi(): JobApi
    {
        return $this->jobApi ?? GeneralUtility::makeInstance(JobApi::class, $this->jobs->getApiClient());
    }

    public function getStatApi(): StatApi
    {
        return $this->statApi ?? GeneralUtility::makeInstance(StatApi::class, $this->stats->getApiClient());
    }

    public function getTenantApi(): TenantApi
    {
        return $this->tenantApi ?? GeneralUtility::makeInstance(TenantApi::class, $this->tenants->getApiClient());
    }

    public function getTicketApi(): TicketApi
    {
        return $this->ticketApi ?? GeneralUtility::makeInstance(TicketApi::class, $this->tickets->getApiClient());
    }
}
