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
use Auth0\SDK\Exception\ApiException;
use Bitmotion\Auth0\Domain\Repository\ApplicationRepository;
use GuzzleHttp\Exception\ClientException;
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

    /**
     * @throws ApiException
     * @throws \Bitmotion\Auth0\Exception\InvalidApplicationException
     */
    public function __construct(int $applicationUid = 0)
    {
        if (!$this->authenticationApi instanceof Authentication) {
            $authentication = $this->getAuthentication($applicationUid);
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
    protected function getAuthentication(int $applicationUid): Authentication
    {
        $applicationRepository = GeneralUtility::makeInstance(ApplicationRepository::class);
        $this->application = $applicationRepository->findByUid($applicationUid);

        return $this->getAuthenticationApi();
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

    public function getConnections(): array
    {
        try {
            return $this->connections->getAll();
        } catch (\Exception $exception) {
            $this->logger->error(
                $exception->getCode() . ': ' . $exception->getMessage()
            );
        }

        return [];
    }

    public function getConnectionsForApplication(): array
    {
        $allowedConnections = [];

        foreach ($this->getConnections() as $connection) {
            if (in_array($this->application['id'], $connection['enabled_clients'])) {
                $allowedConnections[] = $connection;
            }
        }

        return $allowedConnections;
    }

    /**
     * @throws \Exception
     */
    public function getUserById(string $userId)
    {
        return $this->users->get($userId);
    }

    protected function getAuthenticationApi(): Authentication
    {
        return new Authentication(
            $this->application['domain'],
            $this->application['id'],
            $this->application['secret'],
            'https://' . $this->application['domain'] . '/' . $this->application['audience']
        );
    }
}
