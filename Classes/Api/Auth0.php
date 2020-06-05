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

namespace Bitmotion\Auth0\Api;

use Auth0\SDK\Exception\CoreException;
use Bitmotion\Auth0\Domain\Repository\ApplicationRepository;
use Bitmotion\Auth0\ErrorCode;
use Bitmotion\Auth0\Exception\InvalidApplicationException;
use Bitmotion\Auth0\Factory\SessionFactory;
use Bitmotion\Auth0\Middleware\CallbackMiddleware;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Auth0 extends \Auth0\SDK\Auth0
{
    /**
     * Error codes provided by Auth0
     * @see: https://auth0.com/docs/libraries/error-messages
     */

    // Sign up errors
    /** @deprecated Use ErrorCodes class instead */
    const ERROR_INVALID_PASSWORD = ErrorCode::ERROR_INVALID_PASSWORD;
    /** @deprecated Use ErrorCodes class instead */
    const ERROR_INVALID_SIGNUP = ErrorCode::ERROR_INVALID_SIGNUP;
    /** @deprecated Use ErrorCodes class instead */
    const ERROR_PASSWORD_DICTIONARY = ErrorCode::ERROR_PASSWORD_DICTIONARY;
    /** @deprecated Use ErrorCodes class instead */
    const ERROR_PASSWORD_NO_USER_INFO = ErrorCode::ERROR_PASSWORD_NO_USER_INFO;
    /** @deprecated Use ErrorCodes class instead */
    const ERROR_PASSWORD_STRENGTH_SIGN_UP = ErrorCode::ERROR_PASSWORD_STRENGTH_SIGN_UP;
    /** @deprecated Use ErrorCodes class instead */
    const ERROR_USER_EXISTS = ErrorCode::ERROR_USER_EXISTS;
    /** @deprecated Use ErrorCodes class instead */
    const ERROR_USERNAME_EXISTS = ErrorCode::ERROR_USERNAME_EXISTS;

    // Login errors
    /** @deprecated Use ErrorCodes class instead. */
    const ERROR_ACCESS_DENIED = ErrorCode::ERROR_ACCESS_DENIED;
    /** @deprecated Use ErrorCodes class instead. */
    const ERROR_INVALID_USER_PASSWORD = ErrorCode::ERROR_INVALID_USER_PASSWORD;
    /** @deprecated Use ErrorCodes class instead. */
    const ERROR_MFA_INVALID_CODE = ErrorCode::ERROR_MFA_INVALID_CODE;
    /** @deprecated Use ErrorCodes class instead. */
    const ERROR_MFA_REGISTRATION_REQUIRED = ErrorCode::ERROR_MFA_REGISTRATION_REQUIRED;
    /** @deprecated Use ErrorCodes class instead. */
    const ERROR_MFA_REQUIRED = ErrorCode::ERROR_MFA_REQUIRED;
    /** @deprecated Use ErrorCodes class instead. */
    const ERROR_PASSWORD_LEAKED = ErrorCode::ERROR_PASSWORD_LEAKED;
    /** @deprecated Use ErrorCodes class instead. */
    const ERROR_PASSWORD_HISTORY = ErrorCode::ERROR_PASSWORD_HISTORY;
    /** @deprecated Use ErrorCodes class instead. */
    const ERROR_PASSWORD_STRENGTH_LOG_IN = ErrorCode::ERROR_PASSWORD_STRENGTH_LOG_IN;
    /** @deprecated Use ErrorCodes class instead. */
    const ERROR_TOO_MANY_ATTEMPTS = ErrorCode::ERROR_TOO_MANY_ATTEMPTS;

    // Errors occurs during log in and sign up
    /** @deprecated Use ErrorCodes class instead. */
    const ERROR_UNAUTHORIZED = ErrorCode::ERROR_UNAUTHORIZED;

    /**
     * @throws CoreException
     */
    public function __construct(?int $application = null, ?string $redirectUri = null, ?string $scope = null, array $additionalOptions = [])
    {
        $config = [
            'persist_access_token' => true,
            'persist_id_token' => true,
            'persist_refresh_token' => true,
            'redirect_uri' => $redirectUri ?? $this->getCallbackUri(),
            'scope' => $scope,
            'store' => (new SessionFactory())->getSessionStoreForApplication((int)$application),
        ];

        if ((int)$application > 0) {
            $this->enrichConfigByApplication($application, $config);
        }

        parent::__construct(array_merge($config, $additionalOptions));
    }

    public function getLogoutUri(string $returnUrl, string $clientId, bool $federated = false): string
    {
        return $this->authentication->get_logout_link($returnUrl, $clientId, $federated);
    }

    protected function getCallbackUri(): string
    {
        return GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST') . CallbackMiddleware::PATH;
    }

    protected function enrichConfigByApplication(int $applicationId, array &$config): void
    {
        try {
            $application = GeneralUtility::makeInstance(ApplicationRepository::class)->findByUid($applicationId, true);
        } catch (InvalidApplicationException $exception) {
            // TODO: Log this

            return;
        }

        $config['audience'] = $application->getAudience(true);
        $config['client_id'] = $application->getClientId();
        $config['client_secret'] = $application->getClientSecret();
        $config['domain'] = $application->getDomain();
        $config['id_token_alg'] = $application->getSignatureAlgorithm();
        $config['secret_base64_encoded'] = $application->isSecretBase64Encoded();
    }
}
