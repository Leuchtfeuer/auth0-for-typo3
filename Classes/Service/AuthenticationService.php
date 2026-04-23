<?php

declare(strict_types=1);

/*
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) Leuchtfeuer Digital Marketing <dev@Leuchtfeuer.com>
 */

namespace Leuchtfeuer\Auth0\Service;

use Auth0\SDK\Auth0;
use Auth0\SDK\Exception\ArgumentException;
use Auth0\SDK\Exception\NetworkException;
use Auth0\SDK\Utility\HttpResponse;
use GuzzleHttp\Exception\GuzzleException;
use Leuchtfeuer\Auth0\Domain\Transfer\EmAuth0Configuration;
use Leuchtfeuer\Auth0\ErrorCode;
use Leuchtfeuer\Auth0\Factory\ApplicationFactory;
use Leuchtfeuer\Auth0\LoginProvider\Auth0Provider;
use Leuchtfeuer\Auth0\Utility\Database\UpdateUtilityFactory;
use Leuchtfeuer\Auth0\Utility\TokenUtility;
use Leuchtfeuer\Auth0\Utility\UserUtility;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;
use TYPO3\CMS\Core\Authentication\AuthenticationService as BasicAuthenticationService;
use TYPO3\CMS\Core\Authentication\LoginType;
use TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Database\ConnectionPool;

class AuthenticationService extends BasicAuthenticationService
{
    private const BACKEND_AUTHENTICATION = 'BE';

    /**
     * @var array<string, mixed>|false
     */
    protected array|false $user = false;

    /**
     * @var array<string, mixed>
     */
    protected array $userInfo = [];

    protected string $tableName = 'be_users';

    protected Auth0 $auth0;

    protected int $application = 0;

    protected string $userIdentifier = '';

    private bool $auth0Authentication = false;

    protected bool $loginViaSession = false;

    private ?ServerRequestInterface $request = null;

    public function __construct(
        protected readonly ConnectionPool $connectionPool,
        protected readonly Random $random,
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

        if (!$this->isAuth0LoginProvider($authInfo['loginType'] ?? '')) {
            return;
        }

        if ($this->hasAuth0Error()) {
            return;
        }

        if ($this->initApplication($authInfo['loginType'] ?? '') === false) {
            $this->logger?->debug('Initialization of Auth0 application failed.');
            return;
        }

        $this->auth0Authentication = true;

        if ($this->initializeAuth0Connection()) {
            if ($this->userInfo !== []) {
                $this->login['status'] = LoginType::LOGIN->value;
                $this->loginViaSession = true;
                if (empty($this->login['uname'])) {
                    $this->login['uname'] = $this->userInfo['email'] ?? $this->userInfo[$this->userIdentifier] ?? '';
                }
            }
            $this->handleLogin();
        }
    }

    /**
     * @param array<string, mixed> $loginData
     */
    public function processLoginData(array &$loginData): bool|int
    {
        /**
         * Note: processLoginData() is called before initAuth() in the TYPO3 lifecycle.
         * At this point, $this->authInfo and $this->request might not be initialized yet,
         * which can cause isAuth0LoginProvider() to return false.
         */
        $loginType = $this->authInfo['loginType'] ?? '';

        if ($this->isAuth0LoginProvider($loginType)) {
            // Set username and dummy password to satisfy Core
            if (empty($loginData['uname'])) {
                $loginData['uname'] = $this->userInfo['email'] ?? $this->userInfo[$this->userIdentifier] ?? '';
            }
            if (empty($loginData['uident_text'])) {
                $loginData['uident_text'] = $this->random->generateRandomHexString(32);
            }
        }
        return true;
    }

    private function isAuth0LoginProvider(string $loginType): bool
    {
        $parsedBody = $this->request?->getParsedBody();
        $loginProvider = (int)($this->request?->getQueryParams()['loginProvider'] ?? (is_array($parsedBody) ? ($parsedBody['loginProvider'] ?? 0) : 0));
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
            case self::BACKEND_AUTHENTICATION:
                $this->logger?->info('Handle backend login.');
                $this->application = $configuration->getBackendConnection();
                break;

            default:
                /** @extensionScannerIgnoreLine */
                $this->logger?->error('Environment is not in backend mode.');
        }

        if ($this->application === 0 && $this->initSessionStore($loginType) === false) {
            /** @extensionScannerIgnoreLine */
            $this->logger?->error('No Auth0 application UID given.');

            return false;
        }

        return true;
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
        } catch (ArgumentException|NetworkException|\JsonException $e) {
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
        }
    }

    /**
     * Initializes the connection to the Auth0 server
     */
    protected function initializeAuth0Connection(): bool
    {
        try {
            $this->auth0 = ApplicationFactory::build($this->application, $this->authInfo['loginType'], $this->request);

            $this->userInfo = $this->auth0->getUser() ?? [];
            $this->logger?->debug('Auth0 user info in AuthenticationService: ' . (empty($this->userInfo) ? 'empty' : 'found'));

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

        if (is_array($this->user) && $this->user !== []) {
            return $this->user;
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

            if (!$this->loginViaSession) {
                $this->writelog(255, 3, 3, null, 'Login-attempt from ###IP###, username \'%s\' not found!!', [$this->login['uname']]);
                $this->logger?->info(
                    sprintf('Login-attempt from username "%s" not found!', $this->login['uname']),
                    [
                        'REMOTE_ADDR' => $this->authInfo['REMOTE_ADDR'],
                    ]
                );
            }
        }

        $this->user = is_array($user) ? $user : false;

        return $this->user;
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

        if ($this->loginViaSession) {
            return 200;
        }

        //        // Do not login if email address is not verified (only available if API is enabled)
        //        // TODO:: Support this even API is disabled
        //        if ($this->auth0User !== null && !$this->auth0User->isEmailVerified()) {
        //            $this->logger?->warning('Email not verified. Do not login user.');
        //            // Responsible, authentication failed, do NOT check other services
        //            return 0;
        //        }

        // Success
        $this->logger?->notice(sprintf('Auth0 User %s (UID: %s) successfully logged in.', $user['auth0_user_id'], $user['uid']));
        return 200;
    }
}
