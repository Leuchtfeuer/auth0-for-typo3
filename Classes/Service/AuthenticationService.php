<?php
declare(strict_types=1);

namespace Bitmotion\Auth0\Service;

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
use Bitmotion\Auth0\Api\AuthenticationApi;
use Bitmotion\Auth0\Api\ManagementApi;
use Bitmotion\Auth0\Domain\Model\Application;
use Bitmotion\Auth0\Domain\Model\Dto\EmAuth0Configuration;
use Bitmotion\Auth0\Domain\Repository\ApplicationRepository;
use Bitmotion\Auth0\Utility\UpdateUtility;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * Class AuthenticationService
 * @package Bitmotion\Auth0\Service
 */
class AuthenticationService extends \TYPO3\CMS\Sv\AuthenticationService
{
    /**
     * @var \stdClass
     */
    protected $auth0Response;

    /**
     * @var array
     */
    protected $auth0Data = [];

    /**
     * @var array
     */
    protected $user = [];

    /**
     * @var array
     */
    protected $tokenInfo = [];

    /**
     * @var string
     */
    protected $tableName = 'fe_users';

    /**
     * @var \stdClass
     */
    protected $auth0User = null;

    /**
     * @param string                                                    $mode
     * @param array                                                     $loginData
     * @param array                                                     $authInfo
     * @param \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication $pObj
     *
     * @throws \Auth0\SDK\Exception\ApiException
     * @throws \Auth0\SDK\Exception\CoreException
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    public function initAuth($mode, $loginData, $authInfo, $pObj)
    {
        $authInfo['db_user']['check_pid_clause'] = false;
        $this->db_user = $authInfo['db_user'];
        $this->db_groups = $authInfo['db_groups'];
        $this->mode = $mode;
        $this->login = $loginData;
        $this->authInfo = $authInfo;

        $this->initializeAuth0Connections();

        if ($mode === 'getUserFE' && !empty($loginData)) {
            $this->tableName = 'fe_users';

            if (!$this->checkIfUserExists()) {
                $this->insertFeUser();
            }
        } elseif ($mode === 'getUserBE' && !empty($loginData)) {
            $this->tableName = 'be_users';

            // TODO: insert user when email is not verified?

            if (!$this->checkIfUserExists()) {
                // Insert new BE User
                $this->insertBeUser();
            } elseif ($this->shouldUpdate()) {
                // Update existing user
                $updateUtility = GeneralUtility::makeInstance(UpdateUtility::class, $this->tableName, $this->auth0User);
                $updateUtility->updateUser();
            }
        }
        $this->pObj = $pObj;
    }

    /**
     * @throws \Auth0\SDK\Exception\ApiException
     * @throws \Auth0\SDK\Exception\CoreException
     * @throws \Exception
     * @todo: Make Application UID dynamic
     */
    protected function initializeAuth0Connections()
    {
        if (TYPO3_MODE === 'FE') {
            $GLOBALS['TSFE']->sys_page = GeneralUtility::makeInstance(PageRepository::class);
            $GLOBALS['TSFE']->sys_page->init(false);
            $applicationUid = 1;
        } else {
            $emConfiguration = new EmAuth0Configuration();
            $applicationUid = $emConfiguration->getBackendConnection();
        }

        /** @var Application $application */
        $applicationRepository = GeneralUtility::makeInstance(ObjectManager::class)->get(ApplicationRepository::class);
        $application = $applicationRepository->findByUid($applicationUid);
        $authenticationApi = new AuthenticationApi($application, 'http://auth0.test/typo3/?loginProvider=1526966635&login=1', 'read:user read:current_user update:current_user_metadata delete:current_user_metadata create:current_user_metadata create:current_user_device_credentials delete:current_user_device_credentials update:current_user_identities openid');
        $this->tokenInfo = $authenticationApi->getUser();
        $managementApi = GeneralUtility::makeInstance(ManagementApi::class, $application);
        $this->auth0User = $managementApi->getUserById($this->tokenInfo['sub']);
    }

    /**
     * @return bool
     */
    protected function shouldUpdate()
    {
        return (strtotime($this->auth0User['updated_at']) > $this->user['tstamp']);
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @deprecated
     */
    protected function processAuth0Login()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_auth0_domain_model_application');
        $application = $queryBuilder
            ->select('*')
            ->from('tx_auth0_domain_model_application')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)))
            ->execute()
            ->fetch();

        $auth0Data = GeneralUtility::_GP('tx_auth0_loginform');
        $client = new Client(['base_uri' => 'https://' . $application['domain']]);
        $request = $client->request(
            'POST',
            'oauth/token',
            [
                'form_params' => [
                    'grant_type' => 'password',
                    'client_id' => $application['id'],
                    'client_secret' => $application['secret'],
                    'audience' => 'https://' . $application['domain'] . '/' . $application['audience'],
                    'username' => $auth0Data['username'],
                    'password' => $auth0Data['password'],
                    'scope' => 'read:current_user update:current_user_metadata delete:current_user_metadata create:current_user_metadata create:current_user_device_credentials delete:current_user_device_credentials update:current_user_identities openid'
                ],
                'version' => '1.1',
                'http_errors' => false,
            ]);

        if ($request instanceof ResponseInterface) {
            $this->auth0Response = \GuzzleHttp\json_decode($request->getBody()->getContents(), true);
        }

        if (isset($this->auth0Response['access_token'])) {
            $request = $client->request(
                'GET',
                'userinfo',
                [
                    'headers' => [
                        'authorization' => 'Bearer ' . $this->auth0Response['access_token']
                    ],
                    'http_errors' => false,
                ]
            );

            if ($request instanceof ResponseInterface) {
                $this->tokenInfo = \GuzzleHttp\json_decode($request->getBody()->getContents(), true);
                if ($this->tokenInfo->email_verified === false) {
                    return false;
                }
            }

            $request = $client->request(
                'GET',
                'api/v2/users/' . $this->tokenInfo['sub'],
                [
                    'headers' => [
                        'authorization' => 'Bearer ' . $this->auth0Response['access_token']
                    ]
                ]
            );

            if ($request instanceof ResponseInterface) {
                $this->auth0User = \GuzzleHttp\json_decode($request->getBody()->getContents(), true);
            }

            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    protected function checkIfUserExists()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);
        $user = $queryBuilder
            ->select('*')
            ->from($this->tableName)
            ->where($queryBuilder->expr()->eq('auth0_user_id', $queryBuilder->createNamedParameter($this->tokenInfo['sub'])))
            ->execute()
            ->fetch();

        if ($user === false) {
            return false;
        }

        $this->user = $user;

        return true;
    }

    /**
     *
     */
    protected function insertFeUser()
    {
        $emConfiguration = new EmAuth0Configuration();
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);
        $queryBuilder
            ->insert($this->tableName)
            ->values(
                [
                    'tx_extbase_type' => 'Tx_Auth0_FrontendUser',
                    'pid' => $emConfiguration->getUserStoragePage(),
                    'tstamp' => time(),
                    'username' => $this->auth0User['email'],
                    'password' => GeneralUtility::makeInstance(Random::class)->generateRandomHexString(50),
                    'email' => $this->auth0User['email'],
                    'crdate' => time(),
                    'auth0_user_id' => $this->auth0User['user_id'],
                    'auth0_metadata' => \GuzzleHttp\json_encode($this->auth0User['user_metadata']),
                ]
            )->execute();
    }

    /**
     *
     */
    protected function insertBeUser()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);
        $queryBuilder
            ->insert($this->tableName)
            ->values(
                [
                    'pid' => 0,
                    'tstamp' => time(),
                    'username' => $this->auth0User->email,
                    'password' => GeneralUtility::makeInstance(Random::class)->generateRandomHexString(50),
                    'email' => $this->auth0User->email,
                    'crdate' => time(),
                    'auth0_user_id' => $this->auth0User->user_id,
                ]
            )->execute();
    }

    /**
     * @return bool|mixed
     */
    public function getUser()
    {
        if ($this->login['status'] !== 'login') {
            return false;
        }

        $user = $this->fetchUserRecord($this->login['uname'], 'auth0_user_id = "' . $this->tokenInfo['sub'] . '"');

        if (!is_array($user)) {
            // Failed login attempt (no username found)
            $this->writelog(255, 3, 3, 2, 'Login-attempt from %s (%s), username \'%s\' not found!!', [$this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $this->login['uname']]);
            // Logout written to log
            GeneralUtility::sysLog(sprintf('Login-attempt from %s (%s), username \'%s\' not found!', $this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $this->login['uname']), 'core', GeneralUtility::SYSLOG_SEVERITY_WARNING);
        } else {
            if ($this->writeDevLog) {
                GeneralUtility::devLog('User found: ' . GeneralUtility::arrayToLogString($user, [$this->db_user['userid_column'], $this->db_user['username_column']]), self::class);
            }
        }

        return $user;
    }

    /**
     * @param array $user
     *
     * @return int
     */
    public function authUser(array $user): int
    {
        // Login user
        if ($user['auth0_user_id'] !== ''  && $user['auth0_user_id'] == $this->tokenInfo['sub']) {

            // Do not login if email address is not verified
            if ($this->auth0User['email_verified'] === false && ($this->mode === 'getUserBE' || (bool)$this->auth0Data['loginIfMailIsNotVerified'] === false)) {
                return 0;
            }

            return 200;
        }

        // Service is not responsible for login request
        return 100;
    }
}