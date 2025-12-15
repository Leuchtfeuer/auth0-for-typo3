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

use GuzzleHttp\Exception\GuzzleException;
use Leuchtfeuer\Auth0\Domain\Transfer\EmAuth0Configuration;
use Leuchtfeuer\Auth0\Factory\ApplicationFactory;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

/**
 * Validates Auth0 session for sudo mode bypass.
 *
 * This service only checks if a user is Auth0-authenticated with a valid session.
 * Authorization/permission checks are handled by TYPO3's core authorization system.
 */
class Auth0SessionValidator implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected EmAuth0Configuration $configuration;

    public function __construct(
        EmAuth0Configuration $configuration
    ) {
        $this->configuration = $configuration;
    }

    /**
     * Check if current user has a valid Auth0 session.
     *
     * This method only checks Auth0 authentication status. Permission checks are
     * handled by TYPO3's authorization system - if the user doesn't have permission
     * to perform an operation, TYPO3 will deny it before sudo mode is even checked.
     *
     * @return bool True if user is Auth0-authenticated with valid session, false otherwise
     */
    public function hasValidAuth0Session(): bool
    {
        // 1. Get current backend user
        $backendUser = $this->getCurrentBackendUser();
        if (!$backendUser instanceof BackendUserAuthentication) {
            if ($this->logger instanceof LoggerInterface) {
                $this->logger->debug('No backend user found in session.');
            }
            return false;
        }

        // 2. Check if current user is Auth0 user
        $currentUserRecord = $backendUser->user;
        if (!$this->isAuth0User($currentUserRecord)) {
            if ($this->logger instanceof LoggerInterface) {
                $this->logger->debug('Current user is not an Auth0 user.');
            }
            return false;
        }

        // 3. Verify Auth0 session is valid
        $applicationUid = $this->configuration->getBackendConnection();
        if (!$this->hasAuth0Session($applicationUid)) {
            if ($this->logger instanceof LoggerInterface) {
                $this->logger->warning('No valid Auth0 session found.');
            }
            return false;
        }

        if ($this->logger instanceof LoggerInterface) {
            $this->logger->info(sprintf(
                'Auth0 user %s bypassing sudo mode (valid Auth0 session).',
                $currentUserRecord['username']
            ));
        }

        return true;
    }

    /**
     * Get the current backend user from global context.
     *
     * @return BackendUserAuthentication|null
     */
    protected function getCurrentBackendUser(): ?BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'] ?? null;
    }

    /**
     * Check if a user record belongs to an Auth0 user.
     *
     * @param array<string, mixed> $userRecord The user record
     * @return bool True if user has auth0_user_id populated
     */
    protected function isAuth0User(array $userRecord): bool
    {
        return isset($userRecord['auth0_user_id']) && !empty($userRecord['auth0_user_id']);
    }

    /**
     * Check if there is a valid Auth0 session.
     *
     * @param int $applicationUid The Auth0 application UID
     * @return bool True if valid session exists
     * @throws GuzzleException
     */
    protected function hasAuth0Session(int $applicationUid): bool
    {
        try {
            $auth0 = ApplicationFactory::build($applicationUid, ApplicationFactory::SESSION_PREFIX_BACKEND);
            $userInfo = $auth0->configuration()->getSessionStorage()?->get('user') ?? [];

            if (!is_array($userInfo) || empty($userInfo)) {
                if ($this->logger instanceof LoggerInterface) {
                    $this->logger->debug('Auth0 session storage is empty.');
                }
                return false;
            }

            // Check if session has required identifier (e.g., 'sub')
            $userIdentifier = $this->configuration->getUserIdentifier();
            if (!isset($userInfo[$userIdentifier])) {
                if ($this->logger instanceof LoggerInterface) {
                    $this->logger->debug('Auth0 session missing user identifier.');
                }
                return false;
            }

            return true;
        } catch (\Exception $exception) {
            if ($this->logger instanceof LoggerInterface) {
                $this->logger->error(sprintf(
                    'Error checking Auth0 session: %s',
                    $exception->getMessage()
                ));
            }
            return false;
        }
    }
}
