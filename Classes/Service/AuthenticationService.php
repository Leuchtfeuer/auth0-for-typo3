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

use Auth0\SDK\Exception\ApiException;
use Auth0\SDK\Store\SessionStore;
use Bitmotion\Auth0\Api\Auth0;
use Bitmotion\Auth0\Domain\Model\Auth0\User;
use Bitmotion\Auth0\Domain\Model\Dto\EmAuth0Configuration;
use Bitmotion\Auth0\Exception\InvalidApplicationException;
use Bitmotion\Auth0\Scope;
use Bitmotion\Auth0\Utility\ApiUtility;
use Bitmotion\Auth0\Utility\Database\UpdateUtility;
use Bitmotion\Auth0\Utility\UserUtility;
use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\EnvironmentService;

class AuthenticationService extends \TYPO3\CMS\Core\Authentication\AuthenticationService
{
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
     */
    protected $tokenInfo = [];

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
     * @param string                                                    $mode
     * @param array                                                     $loginData
     * @param array                                                     $authInfo
     * @param \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication $pObj
     *
     * @throws \Auth0\SDK\Exception\ApiException
     * @throws \Auth0\SDK\Exception\CoreException
     * @throws \TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException
     * @throws \TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException
     * @throws \TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException
     */
    public function initAuth($mode, $loginData, $authInfo, $pObj)
    {
        if ($this->isResponsible() === false) {
            return;
        }

        $this->initApplication();

        if ($this->application === 0) {
            return;
        }

        $this->initSessionStore();

        // Set default values
        $this->setDefaults($authInfo, $mode, $loginData, $pObj);

        if ($this->loginViaSession === true) {
            $this->login['status'] = 'login';
            $this->handleLogin($loginData);
        } elseif ($this->initializeAuth0Connections()) {
            $this->handleLogin($loginData);
        }
    }

    protected function isResponsible(): bool
    {
        $this->environmentService = GeneralUtility::makeInstance(EnvironmentService::class);
        $responsible = true;

        if ($this->environmentService->isEnvironmentInBackendMode() && GeneralUtility::_GP('loginProvider') !== self::AUTH_LOGIN_PROVIDER) {
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

    protected function setDefaults(array $authInfo, string $mode, array $loginData, AbstractUserAuthentication $pObj)
    {
        $authInfo['db_user']['check_pid_clause'] = false;
        $this->db_user = $authInfo['db_user'];
        $this->db_groups = $authInfo['db_groups'];
        $this->authInfo = $authInfo;
        $this->mode = $mode;
        $this->login = $loginData;
        $this->pObj = $pObj;
    }

    protected function initSessionStore()
    {
        $sessionStore = new SessionStore();
        $tokenInfo = $sessionStore->get('user');

        if (is_array($tokenInfo) && isset($tokenInfo['sub'])) {
            $this->logger->debug('Try to login user via Auth0 session');
            try {
                $this->tokenInfo = $tokenInfo;
                $this->getAuth0User();
                $this->loginViaSession = true;
            } catch (\Exception $exception) {
                $this->logger->debug('Could not login user via Auth0 session');
                $this->tokenInfo = [];
                $this->auth0User = null;
                $sessionStore->delete('user');
            }
        }
    }

    /**
     * @throws \TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException
     * @throws \TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException
     * @throws \TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException
     */
    protected function handleLogin(array $loginData)
    {
        // Handle login for frontend or backend
        if ($this->mode === 'getUserFE' && !empty($loginData)) {
            $this->logger->notice('Handle Auth0 login for frontend users');
            $this->tableName = 'fe_users';
            $this->insertOrUpdateUser();
        } elseif ($this->mode === 'getUserBE' && !empty($loginData)) {
            $this->logger->notice('Handle Auth0 login for backend users');
            $this->tableName = 'be_users';
            $this->insertOrUpdateUser();
        } else {
            $this->logger->notice('Login data is empty. Could not login user.');
        }
    }

    /**
     * @throws \Exception
     */
    protected function getAuth0User()
    {
        $apiUtility = GeneralUtility::makeInstance(ApiUtility::class, $this->application);
        $userUtility = $apiUtility->getUserApi(Scope::USER_READ);
        try {
            $this->auth0User = $userUtility->get($this->tokenInfo['sub']);
        } catch (ApiException $apiException) {
            $this->logger->error('No Auth0 user found.');

            return false;
        }

        return true;
    }

    /**
     * Insert or updates user data in TYPO3 database
     *
     * @throws \TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException
     * @throws \TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException
     * @throws \TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException
     */
    protected function insertOrUpdateUser()
    {
        $userUtility = GeneralUtility::makeInstance(UserUtility::class);
        $this->user = $userUtility->checkIfUserExists($this->tableName, $this->tokenInfo['sub']);

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
     * @throws \Auth0\SDK\Exception\ApiException
     * @throws \Auth0\SDK\Exception\CoreException
     * @throws \Exception
     */
    protected function initializeAuth0Connections(): bool
    {
        try {
            $apiUtility = GeneralUtility::makeInstance(ApiUtility::class, $this->application);
            $callback = GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST') . '/typo3/?loginProvider=' . self::AUTH_LOGIN_PROVIDER . '&login=1';
            $this->auth0 = $apiUtility->getAuth0($callback);
            $this->tokenInfo = $this->auth0->getUser();

            if ($this->getAuth0User() === false) {
                return false;
            }

            $this->logger->notice(sprintf('User found: %s', $this->auth0User->getEmail()));

            return true;
        } catch (InvalidApplicationException $exception) {
            $this->logger->emergency(sprintf('Error %s: %s', $exception->getCode(), $exception->getMessage()));
        }

        return false;
    }

    /**
     * @throws \TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException
     * @throws \TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException
     */
    protected function initApplication()
    {
        $applicationUid = 0;

        if ($this->environmentService->isEnvironmentInFrontendMode()) {
            $this->logger->notice('Handle frontend login.');
            $applicationUid = (int)GeneralUtility::_GP('application');
        } elseif ($this->environmentService->isEnvironmentInBackendMode()) {
            $this->logger->notice('Handle backend login.');
            $emConfiguration = new EmAuth0Configuration();
            $applicationUid = $emConfiguration->getBackendConnection();
        } else {
            $this->logger->error('Environment is neither in frontend nor in backend mode');
        }

        if ($applicationUid === 0) {
            $this->logger->error('No Auth0 application UID given.');
        }

        $this->application = $applicationUid;
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
            if ($this->auth0User !== null) {
                $this->auth0->deleteAllPersistentData();
            }

            $this->writelog(255, 3, 3, 2, 'Login-attempt from ###IP###, username \'%s\' not found!!', [$this->login['uname']]);
            $this->logger->info('Login-atttttttempt from username \'' . $this->login['uname'] . '\' not found!', [
                'REMOTE_ADDR' => $this->authInfo['REMOTE_ADDR'],
            ]);
        }

        return $user;
    }

    public function authUser(array $user): int
    {
        // Login user
        if ($user['auth0_user_id'] !== '' && $user['auth0_user_id'] == $this->tokenInfo['sub']) {
            // Do not login if email address is not verified
            if ($this->auth0User->isEmailVerified() === false && ($this->mode === 'getUserBE' || (bool)$this->auth0Data['loginIfMailIsNotVerified'] === false)) {
                $this->logger->notice('Email not verified. Do not login user.');

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
