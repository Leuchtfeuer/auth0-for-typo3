<?php
declare(strict_types = 1);
namespace Bitmotion\Auth0\Service;

/***
 *
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2018 Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 *
 ***/

use Auth0\SDK\Exception\ApiException;
use Auth0\SDK\Exception\CoreException;
use Bitmotion\Auth0\Api\Auth0;
use Bitmotion\Auth0\Domain\Model\Auth0\Management\User;
use Bitmotion\Auth0\Domain\Transfer\EmAuth0Configuration;
use Bitmotion\Auth0\Exception\InvalidApplicationException;
use Bitmotion\Auth0\Factory\SessionFactory;
use Bitmotion\Auth0\LoginProvider\Auth0Provider;
use Bitmotion\Auth0\Scope;
use Bitmotion\Auth0\Utility\ApiUtility;
use Bitmotion\Auth0\Utility\Database\UpdateUtility;
use Bitmotion\Auth0\Utility\UserUtility;
use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\EnvironmentService;

class AuthenticationService extends \TYPO3\CMS\Core\Authentication\AuthenticationService
{
    /**
     * @deprecated Use Auth0Provider::LOGIN_PROVIDER instead
     */
    const AUTH_LOGIN_PROVIDER = '1526966635';

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
     * @deprecated This property is no longer used.
     */
    protected $tokenInfo = [];

    /**
     * @var array
     */
    protected $userInfo = [];

    /**
     * @var string
     */
    protected $tableName = 'fe_users';

    /**
     * @var User
     */
    protected $auth0User;

    /**
     * @var EnvironmentService
     */
    protected $environmentService;

    /**
     * @var Auth0
     */
    protected $auth0;

    /**
     * @var bool
     */
    protected $loginViaSession = false;

    /**
     * @var int
     */
    protected $application = 0;

    /**
     * @param string                     $mode      Subtype of the service which is used to call the service.
     * @param array                      $loginData Submitted login form data
     * @param array                      $authInfo  Information array. Holds submitted form data etc.
     * @param AbstractUserAuthentication $pObj      Parent object
     *
     * @throws ApiException
     * @throws CoreException
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws InvalidPasswordHashException
     */
    public function initAuth($mode, $loginData, $authInfo, $pObj): void
    {
        if ($this->isResponsible() === false) {
            return;
        }

        $this->initApplication();

        // Set default values
        $this->setDefaults($authInfo, $mode, $loginData, $pObj);

        if ($this->loginViaSession === true) {
            $this->login['status'] = 'login';
            $this->login['responsible'] = true;
            $this->handleLogin();
        } elseif ($this->initializeAuth0Connections()) {
            $this->handleLogin();
        }
    }

    protected function isResponsible(): bool
    {
        $this->environmentService = GeneralUtility::makeInstance(EnvironmentService::class);
        $responsible = true;

        if ($this->environmentService->isEnvironmentInBackendMode() && (int)GeneralUtility::_GP('loginProvider') !== Auth0Provider::LOGIN_PROVIDER) {
            $this->logger->notice('Not an Auth0 backend login. Skip.');
            $responsible = false;
        }

        $auth0ErrorCode = GeneralUtility::_GET('error');
        if ($auth0ErrorCode === Auth0::ERROR_ACCESS_DENIED || $auth0ErrorCode === Auth0::ERROR_UNAUTHORIZED) {
            $this->logger->notice('Access denied. Skip.');
            $responsible = false;
        }

        return $responsible;
    }

    /**
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     */
    protected function initApplication(): void
    {
        if ($this->environmentService->isEnvironmentInFrontendMode()) {
            $this->logger->notice('Handle frontend login.');
            $this->application = (int)GeneralUtility::_GP('application');
            $this->tableName = 'fe_users';
        } elseif ($this->environmentService->isEnvironmentInBackendMode()) {
            $this->logger->notice('Handle backend login.');
            $emConfiguration = new EmAuth0Configuration();
            $this->application = $emConfiguration->getBackendConnection();
            $this->tableName = 'be_users';
        } else {
            $this->logger->error('Environment is neither in frontend nor in backend mode');
        }

        if ($this->application === 0 && $this->initSessionStore() === false) {
            $this->logger->error('No Auth0 application UID given.');
        }
    }

    protected function setDefaults(array $authInfo, string $mode, array $loginData, AbstractUserAuthentication $pObj): void
    {
        $authInfo['db_user']['check_pid_clause'] = false;
        $loginData['responsible'] = false;

        $this->db_user = $authInfo['db_user'];
        $this->db_groups = $authInfo['db_groups'];
        $this->authInfo = $authInfo;
        $this->mode = $mode;
        $this->login = $loginData;
        $this->pObj = $pObj;
    }

    protected function initSessionStore(): bool
    {
        // TODO: Add application UID
        $session = (new SessionFactory())->getSessionStoreForApplication(0);
        $userInfo = $session->getUserInfo();

        if (!empty($userInfo['sub'])) {
            $this->logger->debug('Try to login user via Auth0 session');
            try {
                $this->userInfo = $userInfo;
                $this->setApplicationByUser($userInfo['sub']);
                $this->getAuth0User();
                $this->loginViaSession = true;
                $this->login['responsible'] = false;

                return true;
            } catch (\Exception $exception) {
                $this->logger->debug('Could not login user via Auth0 session');
                $this->userInfo = [];
                $this->auth0User = null;
                $session->deleteUserInfo();
            }
        }

        return false;
    }

    protected function setApplicationByUser(string $auth0UserId): void
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);
        $application = $queryBuilder
            ->select('auth0_last_application')
            ->from($this->tableName)
            ->where($queryBuilder->expr()->eq('auth0_user_id', $queryBuilder->createNamedParameter($auth0UserId)))
            ->execute()
            ->fetchColumn();

        $this->logger->debug(sprintf('Found application (ID: %s) for active Auth0 session.', $application));
        $this->application = (int)$application;
    }

    /**
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws InvalidPasswordHashException
     */
    protected function handleLogin(): void
    {
        if ($this->login['responsible'] === true) {
            switch ($this->mode) {
                case 'getUserFE':
                case 'getUserBE':
                    $this->logger->notice('Handle Auth0 login');
                    $this->insertOrUpdateUser();
                    break;
                default:
                    $this->logger->notice('Login data is empty. Could not login user.');
            }
        }
    }

    /**
     * @throws \Exception
     */
    protected function getAuth0User()
    {
        try {
            $userApi =  GeneralUtility::makeInstance(ApiUtility::class, $this->application)->getUserApi(Scope::USER_READ);
            $this->auth0User = $userApi->get($this->userInfo['sub']);
        } catch (ApiException $apiException) {
            $this->logger->error('No Auth0 user found.');

            return false;
        }

        return true;
    }

    /**
     * Insert or updates user data in TYPO3 database
     *
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws InvalidPasswordHashException
     */
    protected function insertOrUpdateUser(): void
    {
        $userUtility = GeneralUtility::makeInstance(UserUtility::class);
        $this->user = $userUtility->checkIfUserExists($this->tableName, $this->userInfo['sub']);

        // Insert a new user into database
        if (empty($this->user)) {
            $this->logger->notice('Insert new user.');
            $userUtility->insertUser($this->tableName, $this->auth0User);
        }

        // Update existing user on every login when we are in BE context
        if ($this->environmentService->isEnvironmentInBackendMode()) {
            $updateUtility = GeneralUtility::makeInstance(UpdateUtility::class, $this->tableName, $this->auth0User);
            $this->logger->notice('Update user.');
            $updateUtility->updateUser();
            $this->logger->notice('Update user groups.');
            $updateUtility->updateGroups();
        } else {
            // Update last uses application
            $userUtility->setLastUsedApplication($this->auth0User->getUserId(), $this->application);
        }
    }

    /**
     * Initializes the connection to the auth0 server
     *
     * @throws ApiException
     * @throws CoreException
     * @throws \Exception
     */
    protected function initializeAuth0Connections(): bool
    {
        try {
            $this->auth0 = GeneralUtility::makeInstance(ApiUtility::class, $this->application)->getAuth0();
            $this->userInfo = $this->auth0->getUser() ?? [];

            if (!isset($this->userInfo['sub']) || $this->getAuth0User() === false) {
                return false;
            }

            $this->login['responsible'] = true;
            $this->logger->notice(sprintf('User found: %s', $this->auth0User->getEmail()));

            return true;
        } catch (InvalidApplicationException $exception) {
            $this->logger->emergency(sprintf('Error %s: %s', $exception->getCode(), $exception->getMessage()));
        }

        return false;
    }

    /**
     * @return bool|mixed
     */
    public function getUser()
    {
        if ($this->login['status'] !== 'login' || $this->login['responsible'] === false || !isset($this->userInfo['sub'])) {
            return false;
        }

        $user = $this->fetchUserRecord($this->login['uname'], 'auth0_user_id = "' . $this->userInfo['sub'] . '"');

        if (!is_array($user)) {
            if ($this->auth0User !== null) {
                $this->auth0->deleteAllPersistentData();
            }

            $this->writelog(255, 3, 3, 2, 'Login-attempt from ###IP###, username \'%s\' not found!!', [$this->login['uname']]);
            $this->logger->info('Login-attempt from username \'' . $this->login['uname'] . '\' not found!', [
                'REMOTE_ADDR' => $this->authInfo['REMOTE_ADDR'],
            ]);
        }

        return $user;
    }

    public function authUser(array $user): int
    {
        // Login user
        if ($this->login['responsible'] && !empty($user['auth0_user_id']) && $user['auth0_user_id'] === $this->tokenInfo['sub']) {
            // Do not login if email address is not verified
            if (!$this->auth0User->isEmailVerified()) {
                $this->logger->notice('Email not verified. Do not login user.');

                return 0;
            }

            // Skip when there is an Auth0 session but the corresponding TYPO3 user hast no user group assigned.
            if (empty($user['usergroup']) && $this->loginViaSession === true) {
                $this->logger->notice('Could not login user via session as it has no group assigned.');

                if (!$this->auth0 instanceof Auth0) {
                    $apiUtility = GeneralUtility::makeInstance(ApiUtility::class, $this->application);
                    $callback = GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST') . '/typo3/?loginProvider=' . Auth0Provider::LOGIN_PROVIDER . '&auth0[action]=login';
                    $this->auth0 = $apiUtility->getAuth0($callback);
                }

                $this->auth0->logout();

                return 0;
            }

            // Success
            $this->logger->notice(sprintf('Auth0 User %s (UID: %s) successfully logged in.', $user['auth0_user_id'], $user['uid']));

            return 200;
        }

        // Service is not responsible for login request
        $this->logger->notice('Auth0 service is not responsible for this request.');

        return 100;
    }
}
