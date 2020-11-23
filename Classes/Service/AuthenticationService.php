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

namespace Bitmotion\Auth0\Service;

use Auth0\SDK\Exception\ApiException;
use Bitmotion\Auth0\Api\Auth0;
use Bitmotion\Auth0\Api\Management\UserApi;
use Bitmotion\Auth0\Domain\Model\Auth0\Management\User;
use Bitmotion\Auth0\Domain\Transfer\EmAuth0Configuration;
use Bitmotion\Auth0\ErrorCode;
use Bitmotion\Auth0\Exception\ApiNotEnabledException;
use Bitmotion\Auth0\Exception\TokenException;
use Bitmotion\Auth0\Factory\SessionFactory;
use Bitmotion\Auth0\LoginProvider\Auth0Provider;
use Bitmotion\Auth0\Middleware\CallbackMiddleware;
use Bitmotion\Auth0\Scope;
use Bitmotion\Auth0\Utility\ApiUtility;
use Bitmotion\Auth0\Utility\Database\UpdateUtility;
use Bitmotion\Auth0\Utility\TokenUtility;
use Bitmotion\Auth0\Utility\UserUtility;
use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;
use TYPO3\CMS\Core\Authentication\AuthenticationService as BasicAuthenticationService;
use TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\EnvironmentService;

class AuthenticationService extends BasicAuthenticationService
{
    /**
     * @deprecated Use Auth0Provider::LOGIN_PROVIDER instead
     */
    const AUTH_LOGIN_PROVIDER = '1526966635';

    /**
     * @var \stdClass
     * @deprecated This property is no longer used.
     */
    protected $auth0Response;

    /**
     * @var array
     * @deprecated This property is no longer used.
     */
    protected $auth0Data = [];

    protected $user = [];

    /**
     * @var array
     * @deprecated This property is no longer used.
     */
    protected $tokenInfo = [];

    protected $userInfo = [];

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

    protected $loginViaSession = false;

    protected $application = 0;

    protected $userIdentifier = '';

    /**
     * @inheritDoc
     *
     * @throws InvalidPasswordHashException
     */
    public function initAuth($mode, $loginData, $authInfo, $pObj): void
    {
        if ($this->isResponsible() === false) {
            $this->logger->debug('Auth0 authentication is not responsible for this request.');
            return;
        }

        if ($this->initApplication() === false) {
            $this->logger->debug('Initialization of Auth0 application failed.');
            return;
        }

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

        // Service is not responsible when environment is in backend mode and the given loginProvider does not match the expected one.
        if ($this->environmentService->isEnvironmentInBackendMode() && (int)GeneralUtility::_GP('loginProvider') !== Auth0Provider::LOGIN_PROVIDER) {
            $this->logger->debug('Not an Auth0 backend login. Skip.');
            $responsible = false;
        }

        // Check whether there was an error during Auth0 calls
        $validErrorCodes = (new \ReflectionClass(ErrorCode::class))->getConstants();
        $auth0ErrorCode = GeneralUtility::_GET('error') ?? '';
        if ($auth0ErrorCode && in_array($auth0ErrorCode, $validErrorCodes)) {
            $this->logger->notice('Access denied. Skip.');
            $responsible = false;
        }

        return $responsible;
    }

    protected function initApplication(): bool
    {
        $extensionConfiguration = GeneralUtility::makeInstance(EmAuth0Configuration::class);
        $this->userIdentifier = $extensionConfiguration->getUserIdentifier();

        if ($this->environmentService->isEnvironmentInFrontendMode()) {
            $this->logger->info('Handle frontend login.');
            $this->application = $this->retrieveApplicationFromUrlQuery();
            $this->tableName = 'fe_users';
        } elseif ($this->environmentService->isEnvironmentInBackendMode()) {
            $this->logger->info('Handle backend login.');
            $this->application = $extensionConfiguration->getBackendConnection();
            $this->tableName = 'be_users';
        } else {
            $this->logger->error('Environment is neither in frontend nor in backend mode.');
        }

        if ($this->application === 0 && $this->initSessionStore() === false) {
            $this->logger->error('No Auth0 application UID given.');

            return false;
        }

        return true;
    }

    protected function retrieveApplicationFromUrlQuery(): int
    {
        $application = (int)GeneralUtility::_GET('application');

        if ($application !== 0) {
            return $application;
        }

        $tokenUtility = GeneralUtility::makeInstance(TokenUtility::class);

        if (!$tokenUtility->verifyToken((string)GeneralUtility::_GET(CallbackMiddleware::TOKEN_PARAMETER))) {
            return 0;
        }

        try {
            $token = $tokenUtility->getToken();
        } catch (TokenException $exception) {
            return 0;
        }

        return (int)$token->getClaim('application');
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

    /**
     * TODO: Maybe deprecate this as the user might not be logged in into Auth0 (Single Log Out).
     * TODO: Or check whether there is a valid Auth0 session.
     */
    protected function initSessionStore(): bool
    {
        // TODO: Add application UID
        $session = (new SessionFactory())->getSessionStoreForApplication(0);
        $userInfo = $session->getUserInfo();

        if (!empty($userInfo[$this->userIdentifier])) {
            $this->logger->debug('Try to login user via Auth0 session');
            try {
                $this->userInfo = $userInfo;
                $this->setApplicationByUser($userInfo[$this->userIdentifier]);
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
     * @throws InvalidPasswordHashException
     */
    protected function handleLogin(): void
    {
        if ($this->login['responsible'] === true) {
            switch ($this->mode) {
                case 'getUserFE':
                case 'getUserBE':
                    $this->insertOrUpdateUser();
                    break;
                case 'authUserFe':
                case 'authUserBe':
                    $this->logger->debug('Skip auth mode.');
                    break;
                default:
                    $this->logger->notice('Undefined mode. Skip.');
            }
        }
    }

    protected function getAuth0User(): bool
    {
        try {
            $apiUtility = GeneralUtility::makeInstance(ApiUtility::class, $this->application);
            $userApi = $apiUtility->getApi(UserApi::class, Scope::USER_READ);
            $this->auth0User = $userApi->get($this->userInfo[$this->userIdentifier]);
        } catch (ApiNotEnabledException $exception) {
            // Do nothing since API is disabled
        } catch (ApiException $apiException) {
            $this->logger->error('No Auth0 user found.');

            return false;
        }

        return true;
    }

    /**
     * Insert or updates user data in TYPO3 database
     *
     * @throws InvalidPasswordHashException
     */
    protected function insertOrUpdateUser(): void
    {
        $userUtility = GeneralUtility::makeInstance(UserUtility::class);
        $this->user = $userUtility->checkIfUserExists($this->tableName, $this->userInfo[$this->userIdentifier]);

        // Insert a new user into database
        if (empty($this->user)) {
            $this->logger->notice('Insert new user.');
            $userUtility->insertUser($this->tableName, $this->auth0User ?? $this->userInfo);
        }

        $updateUtility = GeneralUtility::makeInstance(UpdateUtility::class, $this->tableName, $this->auth0User ?? $this->userInfo);
        $updateUtility->updateGroups();

        // Update existing user on every login when we are in BE context (since TypoScript is loaded).
        if ($this->environmentService->isEnvironmentInBackendMode()) {
            $updateUtility->updateUser();
        } else {
            // Update last used application (no TypoScript loaded in Frontend Requests)
            $userUtility->setLastUsedApplication($this->userInfo[$this->userIdentifier], $this->application);
        }
    }

    /**
     * Initializes the connection to the Auth0 server
     */
    protected function initializeAuth0Connections(): bool
    {
        try {
            $this->auth0 = GeneralUtility::makeInstance(ApiUtility::class, $this->application)->getAuth0();
            $this->userInfo = $this->auth0->getUser() ?? [];

            if (!isset($this->userInfo[$this->userIdentifier]) || $this->getAuth0User() === false) {
                return false;
            }

            $this->login['responsible'] = true;
            $this->logger->notice(sprintf('Found user with Auth0 identifier "%s".', $this->userInfo[$this->userIdentifier]));

            return true;
        } catch (\Exception $exception) {
            $this->logger->emergency(sprintf('Error %s: %s', $exception->getCode(), $exception->getMessage()));
        }

        return false;
    }

    /**
     * @return bool|mixed
     */
    public function getUser()
    {
        if ($this->login['status'] !== 'login' || $this->login['responsible'] === false || !isset($this->userInfo[$this->userIdentifier])) {
            return false;
        }

        $user = $this->fetchUserRecord($this->login['uname'], 'auth0_user_id = "' . $this->userInfo[$this->userIdentifier] . '"');

        if (!is_array($user)) {
            // Delete persistent Auth0 user data
            $this->auth0->deleteAllPersistentData();

            $this->writelog(255, 3, 3, 2, 'Login-attempt from ###IP###, username \'%s\' not found!!', [$this->login['uname']]);
            $this->logger->info(
                sprintf('Login-attempt from username "%s" not found!', $this->login['uname']),
                [
                    'REMOTE_ADDR' => $this->authInfo['REMOTE_ADDR'],
                ]
            );
        }

        return $user;
    }

    public function authUser(array $user): int
    {
        if ($this->login['responsible'] === false) {
            // Service is not responsible. Check other services.
            return 100;
        }

        if (empty($user['auth0_user_id']) || $user['auth0_user_id'] !== $this->userInfo[$this->userIdentifier]) {
            // Verification failed as identifier does not match. Maybe other services can handle this login.
            return 100;
        }

        // Do not login if email address is not verified (only available if API is enabled)
        // TODO:: Support this even API is disabled
        if ($this->auth0User !== null && !$this->auth0User->isEmailVerified()) {
            $this->logger->warning('Email not verified. Do not login user.');

            // Responsible, authentication failed, do NOT check other services
            return 0;
        }

        // Skip when there is an Auth0 session but the corresponding TYPO3 user has no user group assigned.
        if (empty($user['usergroup']) && $this->loginViaSession === true) {
            $this->logger->warning('Could not login user via session as it has no group assigned.');

            if ($this->auth0 instanceof Auth0 === false) {
                $this->auth0 = GeneralUtility::makeInstance(ApiUtility::class, $this->application)->getAuth0();
            }

            $this->auth0->logout();

            // Responsible, authentication failed, do NOT check other services
            return 0;
        }

        // Success
        $this->logger->notice(sprintf('Auth0 User %s (UID: %s) successfully logged in.', $user['auth0_user_id'], $user['uid']));
        return 200;
    }
}
