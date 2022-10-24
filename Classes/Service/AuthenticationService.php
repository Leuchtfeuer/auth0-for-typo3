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

use Auth0\SDK\Auth0;
use Auth0\SDK\Exception\ArgumentException;
use Auth0\SDK\Exception\NetworkException;
use Auth0\SDK\Utility\HttpResponse;

use Bitmotion\Auth0\Domain\Repository\ApplicationRepository;
use Bitmotion\Auth0\Domain\Transfer\EmAuth0Configuration;
use Bitmotion\Auth0\ErrorCode;
use Bitmotion\Auth0\Exception\TokenException;
use Bitmotion\Auth0\Factory\ApplicationFactory;
use Bitmotion\Auth0\Factory\SessionFactory;
use Bitmotion\Auth0\LoginProvider\Auth0Provider;
use Bitmotion\Auth0\Middleware\CallbackMiddleware;
use Bitmotion\Auth0\Utility\Database\UpdateUtility;
use Bitmotion\Auth0\Utility\TokenUtility;
use Bitmotion\Auth0\Utility\UserUtility;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;
use TYPO3\CMS\Core\Authentication\AuthenticationService as BasicAuthenticationService;
use TYPO3\CMS\Core\Authentication\LoginType;
use TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\SysLog\Action\Login as SystemLogLoginAction;
use TYPO3\CMS\Core\SysLog\Error as SystemLogErrorClassification;
use TYPO3\CMS\Core\SysLog\Type as SystemLogType;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

class AuthenticationService extends BasicAuthenticationService
{
    private const BACKEND_AUTHENTICATION = 'BE';

    private const FRONTEND_AUTHENTICATION = 'FE';

    protected array $user = [];

    protected array $userInfo = [];

    protected string $tableName = 'fe_users';

    /**
     * @var Auth0
     */
    protected $auth0;

    protected bool $loginViaSession = false;

    protected int $applicationId = 0;

    protected string $userIdentifier = '';

    private bool $auth0Authentication = false;

    /**
     * @inheritDoc
     *
     * @throws InvalidPasswordHashException
     */
    public function initAuth($mode, $loginData, $authInfo, $pObj): void
    {
        $this->setDefaults($authInfo, $mode, $loginData, $pObj);

        if ($this->isAuth0LoginProvider($authInfo['loginType'])) {
            $this->logger->debug('Auth0 authentication is not responsible for this request.');
            return;
        }

        if ($this->hasAuth0Error()) {
            return;
        }

        if ($this->initApplication($authInfo['loginType']) === false) {
            $this->logger->debug('Initialization of Auth0 application failed.');
            return;
        }

        $this->auth0Authentication = true;
        $this->initializeAuth0Connection();
        $this->handleLogin();
    }

    private function isAuth0LoginProvider(string $loginType): bool
    {
        return $loginType === self::BACKEND_AUTHENTICATION && (int)GeneralUtility::_GP('loginProvider') !== Auth0Provider::LOGIN_PROVIDER;
    }

    private function hasAuth0Error(): bool
    {
        $validErrorCodes = (new \ReflectionClass(ErrorCode::class))->getConstants();
        $auth0ErrorCode = GeneralUtility::_GET('error') ?? '';
        if ($auth0ErrorCode && in_array($auth0ErrorCode, $validErrorCodes)) {
            $this->logger->notice('Access denied. Skip.');
            return true;
        }
        return false;
    }

    protected function initApplication(string $loginType): bool
    {
        $configuration = new EmAuth0Configuration();

        $this->userIdentifier = $configuration->getUserIdentifier();

        switch ($loginType) {
            case self::FRONTEND_AUTHENTICATION:
                $this->logger->info('Handle frontend login.');
                $this->applicationId = $this->retrieveApplicationFromUrlQuery();
                $this->tableName = 'fe_users';
                break;

            case self::BACKEND_AUTHENTICATION:
                $this->logger->info('Handle backend login.');
                $this->applicationId = $configuration->getBackendConnection();
                $this->tableName = 'be_users';
                break;

            default:
                $this->logger->error('Environment is neither in frontend nor in backend mode.');
        }

        if ($this->applicationId === 0) {
            $this->logger->error('No Auth0 application UID given.');

            return false;
        }

        return true;
    }

    protected function retrieveApplicationFromUrlQuery(): int
    {
        DebuggerUtility::var_dump('retrieveApplicationFromUrlQuery');

        $applicationId = (int)GeneralUtility::_GET('application');

        if ($applicationId !== 0) {
            return $applicationId;
        }

        $tokenUtility = GeneralUtility::makeInstance(TokenUtility::class);

        if (!$tokenUtility->verifyToken((string)GeneralUtility::_GET(CallbackMiddleware::TOKEN_PARAMETER))) {
            return 0;
        }

        try {
            $dataSet = $tokenUtility->getToken()->claims();
        } catch (TokenException $exception) {
            return 0;
        }

        return (int)$dataSet->get('application');
    }

    protected function setDefaults(
        array $authInfo,
        string $mode,
        array $loginData,
        AbstractUserAuthentication $pObj
    ): void {
        $authInfo['db_user']['check_pid_clause'] = false;

        $this->db_user = $authInfo['db_user'];
        $this->authInfo = $authInfo;
        $this->mode = $mode;
        $this->login = $loginData;
        $this->pObj = $pObj;
    }

    protected function setApplicationByUser(string $auth0UserId): void
    {
        DebuggerUtility::var_dump('setApplicationByUser');

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);
        $application = $queryBuilder
            ->select('auth0_last_application')
            ->from($this->tableName)
            ->where($queryBuilder->expr()->eq('auth0_user_id', $queryBuilder->createNamedParameter($auth0UserId)))
            ->execute()
            ->fetchOne();

        $this->logger->debug(sprintf('Found application (ID: %s) for active Auth0 session.', $application));
        $this->applicationId = (int)$application;
    }

    /**
     * @throws InvalidPasswordHashException
     */
    protected function handleLogin(): void
    {
        if ($this->auth0Authentication) {
            switch ($this->mode) {
                case 'getUserFE':
                case 'getUserBE':
                    $this->insertOrUpdateUser();
                    break;
                case 'authUserFE':
                case 'authUserBE':
                    $this->logger->debug(sprintf('Skip auth mode "%s".', $this->mode));
                    break;
                default:
                    $this->logger->notice(sprintf('Undefined mode "%s". Skip.', $this->mode));
            }
        }
    }
    protected function initializeAuth0UserWithManagementApi(): bool
    {
        try {
            $userUtility = GeneralUtility::makeInstance(UserUtility::class);
            $managementUser = HttpResponse::decodeContent($this->auth0->management()->users()->get($this->userInfo[$this->userIdentifier]));
            $this->userInfo = $userUtility->enrichManagementUser($managementUser);
        } catch (ArgumentException|NetworkException|JsonException $e) {
            $this->logger->error($e->getMessage());
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
            $userUtility->insertUser($this->tableName, $this->userInfo);
        }
        $updateUtility = GeneralUtility::makeInstance(UpdateUtility::class, $this->tableName, $this->userInfo);
        $updateUtility->updateGroups();

        // Update existing user on every login when we are in BE context (since TypoScript is loaded).
        if ($this->authInfo['loginType'] === self::BACKEND_AUTHENTICATION) {
            $updateUtility->updateUser();
        } else {
            // Update last used application (no TypoScript loaded in Frontend Requests)
            $userUtility->setLastUsedApplication($this->userInfo[$this->userIdentifier], $this->applicationId);
        }
    }

    /**
     * Initializes the connection to the Auth0 server
     */
    protected function initializeAuth0Connection(): bool
    {
        DebuggerUtility::var_dump('initializeAuth0Connection');
        try {
            $this->auth0 = ApplicationFactory::build($this->applicationId, $this->authInfo['loginType']);
            $application = GeneralUtility::makeInstance(ApplicationRepository::class)->findByUid($this->applicationId);

            if (!$application->hasApi()) {
                $this->auth0->exchange();
                $this->userInfo = $this->auth0->getUser();
            } else {
                $this->userInfo = $this->auth0->getUser();
                $this->initializeAuth0UserWithManagementApi();
            }


            if (!isset($this->userInfo[$this->userIdentifier]) || $this->getAuth0User() === false) {
                return false;
            }

            $this->auth0Authentication = true;
            $this->logger->notice(sprintf('Found user with Auth0 identifier "%s".',
                $this->userInfo[$this->userIdentifier]));

            return true;
        } catch (\Exception $exception) {
            $this->logger->emergency(sprintf('Error %s: %s', $exception->getCode(), $exception->getMessage()));
        } catch (GuzzleException $e) {
            $this->logger->error($e->getMessage());
        }

        return false;
    }

    /**
     * @return bool|mixed
     */
    public function getUser()
    {
        DebuggerUtility::var_dump('getUser');
        if ($this->auth0Authentication === false || !isset($this->userInfo[$this->userIdentifier])) {
            return false;
        }

        $user = $this->fetchUserRecord($this->login['uname'],
            'auth0_user_id = "' . $this->userInfo[$this->userIdentifier] . '"');

        if (!is_array($user)) {
            // Delete persistent Auth0 user data
            try {
                $this->auth0->clear();
            } catch (\Exception $exception) {
                // ignore this...
            }

            $this->writelog(255, 3, 3, 2, 'Login-attempt from ###IP###, username \'%s\' not found!!',
                [$this->login['uname']]);
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

        if ($this->auth0Authentication === false) {
            // Service is not responsible. Check other services.
            return 100;
        }

        if (empty($user['auth0_user_id']) || $user['auth0_user_id'] !== $this->auth0->getUser()[$this->userIdentifier]) {
            // Verification failed as identifier does not match. Maybe other services can handle this login.
            return 100;
        }

//        // Do not login if email address is not verified (only available if API is enabled)
//        // TODO:: Support this even API is disabled
//        if ($this->auth0User !== null && !$this->auth0User->isEmailVerified()) {
//            $this->logger->warning('Email not verified. Do not login user.');
//            // Responsible, authentication failed, do NOT check other services
//            return 0;
//        }

        // Skip when there is an Auth0 session but the corresponding TYPO3 user has no user group assigned.
        if (empty($user['usergroup']) && $this->loginViaSession === true) {
            $this->logger->warning('Could not login user via session as it has no group assigned.');

            // TODO: Pass error message for clarification
            $this->auth0->logout(GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . 'typo3/logout');
            // Responsible, authentication failed, do NOT check other services
            return 0;
        }

        // Success
        $this->logger->notice(sprintf('Auth0 User %s (UID: %s) successfully logged in.', $user['auth0_user_id'],
            $user['uid']));
        return 200;
    }
}
