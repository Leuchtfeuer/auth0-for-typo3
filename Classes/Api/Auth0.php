<?php
declare(strict_types = 1);
namespace Bitmotion\Auth0\Api;

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

use Auth0\SDK\Exception\CoreException;
use Bitmotion\Auth0\Domain\Model\Application;
use Bitmotion\Auth0\Domain\Repository\ApplicationRepository;
use Bitmotion\Auth0\Exception\InvalidApplicationException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Auth0 extends \Auth0\SDK\Auth0
{
    /**
     * Error codes provided by Auth0
     * @see: https://auth0.com/docs/libraries/error-messages
     */

    // Sign up errors
    const ERROR_INVALID_PASSWORD = 'invalid_password';
    const ERROR_PASSWORD_DICTIONARY = 'password_dictionary_error';
    const ERROR_PASSWORD_NO_USER_INFO = 'password_no_user_info_error';
    const ERROR_PASSWORD_STRENGTH_SIGN_UP = 'password_strength_error';
    const ERROR_USER_EXISTS = 'user_exists';
    const ERROR_USERNAME_EXISTS = 'username_exists';

    // Log in errors
    const ERROR_ACCESS_DENIED = 'access_denied';
    const ERROR_INVALID_USER_PASSWORD = 'invalid_user_password';
    const ERROR_MFA_INVALID_CODE = 'mfa_invalid_code';
    const ERROR_MFA_REGISTRATION_REQUIRED = 'mfa_registration_required';
    const ERROR_MFA_REQUIRED = 'mfa_required';
    const ERROR_PASSWORD_LEAKED = 'password_leaked';
    const ERROR_PASSWORD_HISTORY = 'PasswordHistoryError';
    const ERROR_PASSWORD_STRENGTH_LOG_IN = 'PasswordStrengthError';
    const ERROR_TOO_MANY_ATTEMPTS = 'too_many_attempts';

    // Errors occures in log in and sign up
    const ERROR_UNAUTHORIZED = 'unauthorized';

    /**
     * @throws CoreException
     * @throws InvalidApplicationException
     */
    public function __construct(int $applicationUid, string $redirectUri = '', string $scope = '', array $additionalOptions = [])
    {
        $applicationRepository = GeneralUtility::makeInstance(ApplicationRepository::class);
        $application = $applicationRepository->findByUid($applicationUid);
        $idTokenAlg = !empty($application['signature_algorithm']) ? $application['signature_algorithm'] : Application::SIGNATURE_RS256;
        $audience = filter_var($application['audience'], FILTER_VALIDATE_URL) ? $application['audience'] : 'https://' . $application['domain'] . '/' . $application['audience'];

        $config = [
            'domain' => $application['domain'],
            'client_id' => $application['id'],
            'redirect_uri' => $redirectUri,
            'client_secret' => $application['secret'],
            'audience' => $audience,
            'scope' => $scope,
            'persist_access_token' => true,
            'persist_refresh_token' => true,
            'persist_id_token' => true,
            'id_token_alg' => $idTokenAlg,
            'secret_base64_encoded' => (bool)$application['secret_base64_encoded'],
        ];

        parent::__construct(array_merge($config, $additionalOptions));
    }

    public function getLogoutUri(string $returnUrl, string $applicationId): string
    {
        return $this->authentication->get_logout_link($returnUrl, $applicationId);
    }
}
