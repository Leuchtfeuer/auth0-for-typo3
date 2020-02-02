<?php
declare(strict_types=1);

namespace Auth0\SDK\Helpers\Tokens;

use Auth0\SDK\Exception\InvalidTokenException;

/**
 * Class IdTokenVerifier
 *
 * @package Auth0\SDK\Helpers
 */
final class IdTokenVerifier
{

    /**
     * Token issuer base URL expected.
     *
     * @var string
     */
    private $issuer;

    /**
     * Token audience expected.
     *
     * @var string
     */
    private $audience;

    /**
     * Token signature verifier.
     *
     * @var SignatureVerifier
     */
    private $verifier;

    /**
     * Clock tolerance for time-base token checks in seconds.
     *
     * @var integer
     */
    private $leeway = 60;

    /**
     * IdTokenVerifier constructor.
     *
     * @param string            $issuer   Token issuer base URL expected.
     * @param string            $audience Token audience expected.
     * @param SignatureVerifier $verifier Token signature verifier.
     */
    public function __construct(string $issuer, string $audience, SignatureVerifier $verifier)
    {
        $this->issuer   = $issuer;
        $this->audience = $audience;
        $this->verifier = $verifier;
    }

    /**
     * Set a new leeway time for all token checks.
     *
     * @param integer $newLeeway New leeway time for class instance.
     *
     * @return void
     */
    public function setLeeway(int $newLeeway) : void
    {
        $this->leeway = $newLeeway;
    }

    /**
     * Verifies and decodes an OIDC-compliant ID token.
     *
     * @param string $token   Raw JWT string.
     * @param array  $options Options to adjust the verification. Can be:
     *      - "nonce" to check the nonce contained in the token (recommended).
     *      - "max_age" to check the auth_time of the token.
     *      - "time" Unix timestamp to use as the current time for exp, iat, and auth_time checks. Used for testing.
     *      - "leeway" clock tolerance in seconds for the current check only. See $leeway above for default.
     *
     * @return array
     *
     * @throws InvalidTokenException Thrown if:
     *      - ID token is missing (expected but none provided)
     *      - Signature cannot be verified
     *      - Token algorithm is not supported
     *      - Any claim-based test fails
     */
    public function verify(string $token, array $options = []) : array
    {
        if (empty($token)) {
            throw new InvalidTokenException('ID token is required but missing');
        }

        $verifiedToken = $this->verifier->verifyAndDecode( $token );

        /*
         * Issuer checks
         */

        $tokenIss = $verifiedToken->getClaim('iss', false);
        if (! $tokenIss || ! is_string($tokenIss)) {
            throw new InvalidTokenException('Issuer (iss) claim must be a string present in the ID token');
        }

        if ($tokenIss !== $this->issuer) {
            throw new InvalidTokenException( sprintf(
                'Issuer (iss) claim mismatch in the ID token; expected "%s", found "%s"', $this->issuer, $tokenIss
            ) );
        }

        /*
         * Subject check
         */

        $tokenSub = $verifiedToken->getClaim('sub', false);
        if (! $tokenSub || ! is_string($tokenSub)) {
            throw new InvalidTokenException('Subject (sub) claim must be a string present in the ID token');
        }

        /*
         * Audience checks
         */

        $tokenAud = $verifiedToken->getClaim('aud', false);
        if (! $tokenAud || (! is_string($tokenAud) && ! is_array($tokenAud))) {
            throw new InvalidTokenException(
                'Audience (aud) claim must be a string or array of strings present in the ID token'
            );
        }

        if (is_array($tokenAud) && ! in_array($this->audience, $tokenAud)) {
            throw new InvalidTokenException( sprintf(
                'Audience (aud) claim mismatch in the ID token; expected "%s" was not one of "%s"',
                $this->audience,
                implode(', ', $tokenAud)
            ) );
        } else if (is_string($tokenAud) && $tokenAud !== $this->audience) {
            throw new InvalidTokenException( sprintf(
                'Audience (aud) claim mismatch in the ID token; expected "%s", found "%s"', $this->audience, $tokenAud
            ) );
        }

        /*
         * Clock checks
         */

        $now    = $options['time'] ?? time();
        $leeway = $options['leeway'] ?? $this->leeway;

        $tokenExp = $verifiedToken->getClaim('exp', false);
        if (! $tokenExp || ! is_int($tokenExp)) {
            throw new InvalidTokenException('Expiration Time (exp) claim must be a number present in the ID token');
        }

        $expireTime = $tokenExp + $leeway;
        if ($now > $expireTime) {
            throw new InvalidTokenException( sprintf(
                'Expiration Time (exp) claim error in the ID token; current time (%d) is after expiration time (%d)',
                $now,
                $expireTime
            ) );
        }

        $tokenIat = $verifiedToken->getClaim('iat', false);
        if (! $tokenIat || ! is_int($tokenIat)) {
            throw new InvalidTokenException('Issued At (iat) claim must be a number present in the ID token');
        }

        /*
         * Nonce check
         */

        if (! empty($options['nonce'])) {
            $tokenNonce = $verifiedToken->getClaim('nonce', false);
            if (! $tokenNonce || ! is_string($tokenNonce)) {
                throw new InvalidTokenException('Nonce (nonce) claim must be a string present in the ID token');
            }

            if ($tokenNonce !== $options['nonce']) {
                throw new InvalidTokenException( sprintf(
                    'Nonce (nonce) claim mismatch in the ID token; expected "%s", found "%s"',
                    $options['nonce'],
                    $tokenNonce
                ) );
            }
        }

        /*
         * Authorized party check
         */

        if (is_array($tokenAud) && count($tokenAud) > 1) {
            $tokenAzp = $verifiedToken->getClaim('azp', false);
            if (! $tokenAzp || ! is_string($tokenAzp)) {
                throw new InvalidTokenException(
                    'Authorized Party (azp) claim must be a string present in the ID token when Audience (aud) claim has multiple values'
                );
            }

            if ($tokenAzp !== $this->audience) {
                throw new InvalidTokenException( sprintf(
                    'Authorized Party (azp) claim mismatch in the ID token; expected "%s", found "%s"',
                    $this->audience,
                    $tokenAzp
                ) );
            }
        }

        /*
         * Authentication time check
         */

        if (! empty($options['max_age'])) {
            $tokenAuthTime = $verifiedToken->getClaim('auth_time', false);
            if (! $tokenAuthTime || ! is_int($tokenAuthTime)) {
                throw new InvalidTokenException(
                    'Authentication Time (auth_time) claim must be a number present in the ID token when Max Age (max_age) is specified'
                );
            }

            $authValidUntil = $tokenAuthTime + $options['max_age'] + $leeway;

            if ($now > $authValidUntil) {
                throw new InvalidTokenException( sprintf(
                    'Authentication Time (auth_time) claim in the ID token indicates that too much time has passed since the last end-user authentication. Current time (%d) is after last auth at %d',
                    $now,
                    $authValidUntil
                ) );
            }
        }

        $profile = [];
        foreach ($verifiedToken->getClaims() as $claim => $value) {
            $profile[$claim] = $value->getValue();
        }

        return $profile;
    }
}
