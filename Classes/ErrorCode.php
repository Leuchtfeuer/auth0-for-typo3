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

namespace Leuchtfeuer\Auth0;

final class ErrorCode
{
    /**
     * Error codes provided by Auth0
     * @see: https://auth0.com/docs/libraries/common-auth0-library-authentication-errors
     */

    // Sign up errors
    public const ERROR_INVALID_PASSWORD = 'invalid_password';
    public const ERROR_INVALID_SIGNUP = 'invalid_signup';
    public const ERROR_PASSWORD_DICTIONARY = 'password_dictionary_error';
    public const ERROR_PASSWORD_NO_USER_INFO = 'password_no_user_info_error';
    public const ERROR_PASSWORD_STRENGTH_SIGN_UP = 'password_strength_error';
    public const ERROR_USER_EXISTS = 'user_exists';
    public const ERROR_USERNAME_EXISTS = 'username_exists';

    // Login errors
    public const ERROR_ACCESS_DENIED = 'access_denied';
    public const ERROR_INVALID_USER_PASSWORD = 'invalid_user_password';
    public const ERROR_MFA_INVALID_CODE = 'mfa_invalid_code';
    public const ERROR_MFA_REGISTRATION_REQUIRED = 'mfa_registration_required';
    public const ERROR_MFA_REQUIRED = 'mfa_required';
    public const ERROR_PASSWORD_LEAKED = 'password_leaked';
    public const ERROR_PASSWORD_HISTORY = 'PasswordHistoryError';
    public const ERROR_PASSWORD_STRENGTH_LOG_IN = 'PasswordStrengthError';
    public const ERROR_TOO_MANY_ATTEMPTS = 'too_many_attempts';

    // Errors occurs during log in and sign up
    public const ERROR_UNAUTHORIZED = 'unauthorized';
}
