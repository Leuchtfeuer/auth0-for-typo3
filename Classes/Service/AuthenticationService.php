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

namespace Leuchtfeuer\Auth0\Service;

use Auth0\SDK\Auth0;
use Auth0\SDK\Exception\ArgumentException;
use Auth0\SDK\Exception\NetworkException;
use Auth0\SDK\Utility\HttpResponse;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use Lcobucci\JWT\UnencryptedToken;
use Leuchtfeuer\Auth0\Domain\Transfer\EmAuth0Configuration;
use Leuchtfeuer\Auth0\ErrorCode;
use Leuchtfeuer\Auth0\Exception\TokenException;
use Leuchtfeuer\Auth0\Factory\ApplicationFactory;
use Leuchtfeuer\Auth0\LoginProvider\Auth0Provider;
use Leuchtfeuer\Auth0\Middleware\CallbackMiddleware;
use Leuchtfeuer\Auth0\Utility\Database\UpdateUtilityFactory;
use Leuchtfeuer\Auth0\Utility\TokenUtility;
use Leuchtfeuer\Auth0\Utility\UserUtility;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;
use TYPO3\CMS\Core\Authentication\AuthenticationService as BasicAuthenticationService;
use TYPO3\CMS\Core\Authentication\LoginType;
use TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class AuthenticationService extends BasicAuthenticationService
{
    private const BACKEND_AUTHENTICATION = 'BE';

    private const FRONTEND_AUTHENTICATION = 'FE';

    /**
     * @var array<string, mixed>
     */
    protected array $user = [];

    /**
     * @var array<string, mixed>
     */
    protected array $userInfo = [];

    protected string $tableName = 'fe_users';

    protected Auth0 $auth0;

    protected bool $loginViaSession = false;

    protected int $application = 0;

    protected string $userIdentifier = '';

    private bool $auth0Authentication = false;

    private ?ServerRequestInterface $request = null;

    public function __construct(
        protected readonly ConnectionPool $connectionPool,
        protected readonly TokenUtility $tokenUtility,
        protected readonly UpdateUtilityFactory $updateUtilityFactory,
        protected readonly UserUtility $userUtility,
    ) {}

    /**
     * @inheritDoc
     * @param array<mixed> $loginData
     * @param array<string, mixed> $authInfo
     *
     * @throws InvalidPasswordHashException
     */
    public function initAuth($mode, $loginData, $authInfo, $pObj): void
    {
        $this->request = $authInfo['request'] ?? null;
        parent::initAuth($mode, $loginData, $authInfo, $pObj);
        // Set default values
        $this->setDefaults($authInfo, $mode, $loginData, $pObj);

        if ($loginData['status'] !== LoginType::LOGIN->value) {
            return;
        }

        if (!$this->isAuth0LoginProvider($authInfo['loginType'])) {
            $this->logger?->debug('Auth0 authentication is not responsible for this request.');
            return;
        }

        if ($this->hasAuth0Error()) {
            return;
        }

        if ($this->initApplication($authInfo['loginType']) === false) {
            $this->logger?->debug('Initialization of Auth0 application failed.');
            return;
        }

        $this->auth0Authentication = true;

        if ($this->loginViaSession) {
            $this->login['status'] = 'login';
            $this->handleLogin();
        } elseif ($this->initializeAuth0Connection()) {
            $this->handleLogin();
        }
    }

    private function isAuth0LoginProvider(string $loginType): bool
    {
        $loginProvider = (int)($this->request?->getQueryParams()['loginProvider'] ?? $this->request?->getQueryParams()['loginProvider'] ?? null);
        return $loginType === self::BACKEND_AUTHENTICATION && $loginProvider === Auth0Provider::LOGIN_PROVIDER;
    }

    private function hasAuth0Error(): bool
    {
        $validErrorCodes = (new \ReflectionClass(ErrorCode::class))->getConstants();
        $auth0ErrorCode = $this->request?->getQueryParams()['error'] ?? '';
        if ($auth0ErrorCode && in_array($auth0ErrorCode, $validErrorCodes)) {
            $this->logger?->notice('Access denied. Skip.');
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
                $this->logger?->info('Handle frontend login.');
                $this->application = $this->retrieveApplicationFromUrlQuery();
                $this->tableName = 'fe_users';
                break;

            case self::BACKEND_AUTHENTICATION:
                $this->logger?->info('Handle backend login.');
                $this->application = $configuration->getBackendConnection();
                $this->tableName = 'be_users';
                break;

            default:
                /** @extensionScannerIgnoreLine */
                $this->logger?->error('Environment is neither in frontend nor in backend mode.');
        }

        if ($this->application === 0 && $this->initSessionStore($loginType) === false) {
            /** @extensionScannerIgnoreLine */
            $this->logger?->error('No Auth0 application UID given.');

            return false;
        }

        return true;
    }

    protected function retrieveApplicationFromUrlQuery(): int
    {
        $application = (int)($this->request?->getQueryParams()['application'] ?? 0);

        if ($application !== 0) {
            return $application;
        }

        $tokenParameter = (string)($this->request?->getQueryParams()[CallbackMiddleware::TOKEN_PARAMETER] ?? '');
        if (!$this->tokenUtility->verifyToken($tokenParameter)) {
            return 0;
        }

        try {
            $token = $this->tokenUtility->getToken();
            if (!$token instanceof UnencryptedToken) {
                throw new TokenException();
            }
            $dataSet = $token->claims();
        } catch (TokenException) {
            return 0;
        }

        return (int)$dataSet->get('application');
    }

    /**
     * @param array<string, mixed> $authInfo
     * @param array<mixed> $loginData
     */
    protected function setDefaults(array $authInfo, string $mode, array $loginData, AbstractUserAuthentication $pObj): void
    {
        $authInfo['db_user']['check_pid_clause'] = false;
        $loginData['responsible'] = false;

        $this->db_user = $authInfo['db_user'];
        $this->authInfo = $authInfo;
        $this->mode = $mode;
        $this->login = $loginData;
        $this->pObj = $pObj;
    }

    /**
     * TODO: Maybe deprecate this as the user might not be logged in into Auth0 (Single Log Out).
     * TODO: Or check whether there is a valid Auth0 session.
     */
    protected function initSessionStore(string $loginType): bool
    {
        echo 'do not hit';
        die();
    }

    protected function setApplicationByUser(string $auth0UserId): void
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($this->tableName);
        $application = $queryBuilder
            ->select('auth0_last_application')
            ->from($this->tableName)
            ->where($queryBuilder->expr()->eq('auth0_user_id', $queryBuilder->createNamedParameter($auth0UserId)))
            ->executeQuery()
            ->fetchOne();

        $this->logger?->debug(sprintf('Found application (ID: %s) for active Auth0 session.', $application));
        $this->application = (int)$application;
    }

    /**
     * @throws InvalidPasswordHashException
     */
    protected function handleLogin(): void
    {
        if ($this->auth0Authentication) {
            match ($this->mode) {
                'getUserBE' => $this->insertOrUpdateUser(),
                'authUserBE' => $this->logger?->debug(sprintf('Skip auth mode "%s".', $this->mode)),
                default => $this->logger?->notice(sprintf('Undefined mode "%s". Skip.', $this->mode)),
            };
        }
    }

    protected function getAuth0User(): bool
    {
        try {
            $managementUser = HttpResponse::decodeContent($this->auth0->management()->users()->get($this->userInfo[$this->userIdentifier]));
            $this->userInfo = $this->userUtility->enrichManagementUser($managementUser);
        } catch (ArgumentException|NetworkException|JsonException $e) {
            $this->logger?->error($e->getMessage());
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
        $this->user = $this->userUtility->checkIfUserExists($this->tableName, $this->userInfo[$this->userIdentifier]);

        // Insert a new user into database
        if ($this->user === []) {
            $this->logger?->notice('Insert new user.');
            $this->userUtility->insertUser($this->tableName, $this->userInfo);
        }
        $updateUtility = $this->updateUtilityFactory->create($this->tableName, $this->userInfo);
        $updateUtility->updateGroups();

        // Update existing user on every login when we are in BE context (since TypoScript is loaded).
        if ($this->authInfo['loginType'] === self::BACKEND_AUTHENTICATION) {
            $updateUtility->updateUser();
        } else {
            // Update last used application (no TypoScript loaded in Frontend Requests)
            $this->userUtility->setLastUsedApplication($this->userInfo[$this->userIdentifier], $this->application);
        }
    }

    /**
     * Initializes the connection to the Auth0 server
     */
    protected function initializeAuth0Connection(): bool
    {
        try {
            $this->auth0 = ApplicationFactory::build($this->application, $this->authInfo['loginType']);

            $this->userInfo = $this->auth0->getUser() ?? [];

            if (!isset($this->userInfo[$this->userIdentifier]) || $this->getAuth0User() === false) {
                return false;
            }
            $this->auth0Authentication = true;
            $this->logger?->notice(sprintf('Found user with Auth0 identifier "%s".', $this->userInfo[$this->userIdentifier]));

            return true;
        } catch (\Exception $exception) {
            $this->logger?->emergency(sprintf('Error %s: %s', $exception->getCode(), $exception->getMessage()));
        } catch (GuzzleException $e) {
            $this->logger?->error($e->getMessage());
        }

        return false;
    }

    /**
     * @return array<string, mixed>|false User array or FALSE
     */
    public function getUser()
    {
        parent::getUser();
        if ($this->auth0Authentication === false || !isset($this->userInfo[$this->userIdentifier])) {
            return false;
        }

        /** @extensionScannerIgnoreLine */
        $user = $this->fetchUserRecord($this->login['uname'], 'auth0_user_id = "' . $this->userInfo[$this->userIdentifier] . '"');

        if (!is_array($user)) {
            // Delete persistent Auth0 user data
            try {
                $this->auth0->clear();
            } catch (\Exception) {
                // ignore this...
            }

            $this->writelog(255, 3, 3, 2, 'Login-attempt from ###IP###, username \'%s\' not found!!', [$this->login['uname']]);
            $this->logger?->info(
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

        if (empty($user['auth0_user_id']) || $user['auth0_user_id'] !== $this->userInfo[$this->userIdentifier]) {
            // Verification failed as identifier does not match. Maybe other services can handle this login.
            return 100;
        }

        //        // Do not login if email address is not verified (only available if API is enabled)
        //        // TODO:: Support this even API is disabled
        //        if ($this->auth0User !== null && !$this->auth0User->isEmailVerified()) {
        //            $this->logger?->warning('Email not verified. Do not login user.');
        //            // Responsible, authentication failed, do NOT check other services
        //            return 0;
        //        }

        // Skip when there is an Auth0 session but the corresponding TYPO3 user has no user group assigned.
        if (empty($user['usergroup']) && $this->loginViaSession) {
            $this->logger?->warning('Could not login user via session as it has no group assigned.');

            // TODO: Pass error message for clarification
            $this->auth0->logout(GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . 'typo3/logout');
            // Responsible, authentication failed, do NOT check other services
            return 0;
        }

        // Success
        $this->logger?->notice(sprintf('Auth0 User %s (UID: %s) successfully logged in.', $user['auth0_user_id'], $user['uid']));
        return 200;
    }
}
