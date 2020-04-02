<?php
declare(strict_types = 1);
namespace Bitmotion\Auth0\Api;

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

use Auth0\SDK\Exception\CoreException;
use Bitmotion\Auth0\Domain\Repository\ApplicationRepository;
use Bitmotion\Auth0\ErrorCode;
use Bitmotion\Auth0\Exception\InvalidApplicationException;
use Bitmotion\Auth0\Factory\SessionFactory;
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

    // Log in errors
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

    // Errors occures in log in and sign up
    /** @deprecated Use ErrorCodes class instead. */
    const ERROR_UNAUTHORIZED = ErrorCode::ERROR_UNAUTHORIZED;

    /**
     * @throws CoreException
     * @throws InvalidApplicationException
     */
    public function __construct(int $applicationId, string $redirectUri = '/', ?string $scope = null, array $additionalOptions = [])
    {
        $application = GeneralUtility::makeInstance(ApplicationRepository::class)->findByUid($applicationId, true);

        $config = [
            'audience' => $application->getAudience(true),
            'client_id' => $application->getClientId(),
            'client_secret' => $application->getClientSecret(),
            'domain' => $application->getDomain(),
            'id_token_alg' => $application->getSignatureAlgorithm(),
            'persist_access_token' => true,
            'persist_id_token' => true,
            'persist_refresh_token' => true,
            'redirect_uri' => $redirectUri,
            'scope' => $scope,
            'secret_base64_encoded' => $application->isSecretBase64Encoded(),
            'store' => (new SessionFactory())->getSessionStoreForApplication($applicationId),
        ];

        parent::__construct(array_merge($config, $additionalOptions));
    }

    public function getLogoutUri(string $returnUrl, string $clientId, bool $federated = false): string
    {
        return $this->authentication->get_logout_link($returnUrl, $clientId, $federated);
    }
}
