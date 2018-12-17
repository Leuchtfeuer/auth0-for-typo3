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
use Bitmotion\Auth0\Domain\Model\Application;
use GuzzleHttp\Exception\ClientException;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/**
 * Class ManagementApi
 */
class ManagementApi extends Management implements SingletonInterface
{
    /**
     * @var Authentication
     */
    protected $authenticationApi = null;

    /**
     * @var Application
     */
    protected $application = null;

    public function __construct(Application $application)
    {
        /** @var Application $application */
        $authenticationApi = new Authentication(
            $application->getDomain(),
            $application->getId(),
            $application->getSecret(),
            'https://' . $application->getDomain() . '/' . $application->getAudience()
        );

        try {
            $result = $authenticationApi->client_credentials([
                'client_secret' => $application->getSecret(),
                'client_id' => $application->getId(),
                'audience' => 'https://' . $application->getDomain() . '/' . $application->getAudience(),
            ]);

            $this->application = $application;
            $this->authenticationApi = $authenticationApi;

            parent::__construct(
                $result['access_token'],
                $application->getDomain(),
                [
                    'http_errors' => false,
                ]
            );
        } catch (ClientException $clientException) {
            // TODO: Handle
            DebuggerUtility::var_dump($clientException, __CLASS__ . ':' . __LINE__);
        } catch (ApiException $apiException) {
            // TODO: Handle
            DebuggerUtility::var_dump($apiException, __CLASS__ . ':' . __LINE__);
        }
    }

    public function getConnections(): array
    {
        try {
            return $this->connections->getAll();
        } catch (\Exception $exception) {
            DebuggerUtility::var_dump($exception, __CLASS__ . ':' . __LINE__);
        }

        return [];
    }

    public function getConnectionsForApplication(): array
    {
        $allowedConnections = [];

        foreach ($this->getConnections() as $connection) {
            if (in_array($this->application->getId(), $connection['enabled_clients'])) {
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
}
