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

namespace Leuchtfeuer\Auth0\Tests\Functional\Fixtures;

/**
 * A throwaway RSA key pair with a self-signed certificate, so a test can sign an
 * `id_token` the Auth0 SDK accepts. The SDK verifies RS256 tokens against the
 * `x5c` certificate chain from the tenant's JWKS, not against a raw modulus,
 * which is why a certificate is generated rather than just a key.
 */
final class RsaKeyPair
{
    public const KEY_ID = 'functional-test-key';

    private string $privateKeyPem;

    private string $certificateBody;

    public function __construct(string $commonName = 'tenant.example.com')
    {
        $key = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        if ($key === false) {
            throw new \RuntimeException('Could not generate a test RSA key: ' . openssl_error_string());
        }

        $csr = openssl_csr_new(['commonName' => $commonName], $key, ['digest_alg' => 'sha256']);
        $certificate = openssl_csr_sign($csr, null, $key, 1, ['digest_alg' => 'sha256']);

        if ($csr === false || $certificate === false) {
            throw new \RuntimeException('Could not generate a test certificate: ' . openssl_error_string());
        }

        openssl_pkey_export($key, $privateKeyPem);
        openssl_x509_export($certificate, $certificatePem);

        $this->privateKeyPem = (string)$privateKeyPem;
        $this->certificateBody = (string)preg_replace(
            '/-----[^-]+-----|\s+/',
            '',
            (string)$certificatePem
        );
    }

    public function getPrivateKeyPem(): string
    {
        return $this->privateKeyPem;
    }

    /**
     * The JWKS document the SDK expects at the tenant's well-known endpoint.
     *
     * @return array{keys: list<array<string, mixed>>}
     */
    public function toJwks(): array
    {
        return [
            'keys' => [
                [
                    'alg' => 'RS256',
                    'kty' => 'RSA',
                    'use' => 'sig',
                    'kid' => self::KEY_ID,
                    'x5c' => [$this->certificateBody],
                ],
            ],
        ];
    }
}
