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

namespace Leuchtfeuer\Auth0\Utility;

use DateTimeImmutable;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Hmac;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Leuchtfeuer\Auth0\Domain\Transfer\EmAuth0Configuration;
use Leuchtfeuer\Auth0\Exception\TokenException;
use Leuchtfeuer\Auth0\Middleware\CallbackMiddleware;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Page\PageInformation;

class TokenUtility implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected const KEY_TYPE_PRIVATE = 'private';

    protected const KEY_TYPE_PUBLIC = 'public';

    public const ENVIRONMENT_FRONTEND = 'FE';

    public const ENVIRONMENT_BACKEND = 'BE';

    protected EmAuth0Configuration $configuration;

    protected DateTimeImmutable $time;

    protected string $issuer = '';

    /**
     * @var array<mixed>
     */
    protected array $payload = [];

    protected ?Token $token = null;

    protected bool $verified = false;

    protected Configuration $config;

    public function __construct()
    {
        $this->configuration = new EmAuth0Configuration();
        $this->time = new DateTimeImmutable();
        $this->setIssuer();
        $this->config = Configuration::forAsymmetricSigner(
            $this->getSigner(),
            $this->getKey(self::KEY_TYPE_PRIVATE),
            $this->getKey(self::KEY_TYPE_PUBLIC)
        );
        $this->config->setValidationConstraints(...$this->getConstraints());
    }

    /**
     * @return Constraint[]
     */
    private function getConstraints(): array
    {
        $issuer = $this->getIssuer();
        if ($issuer === '') {
            throw new \RuntimeException('Issuer must not be empty');
        }

        $contraints[] = new IssuedBy($issuer);
        $contraints[] = new PermittedFor(CallbackMiddleware::PATH);
        $contraints[] = new SignedWith($this->getSigner(), $this->getKey(self::KEY_TYPE_PUBLIC));
        return $contraints;
    }

    public function buildToken(): UnencryptedToken
    {
        $issuer = $this->getIssuer();
        if ($issuer === '') {
            throw new \RuntimeException('Issuer must not be empty');
        }

        $builder = $this->config->builder();
        $builder = $builder->issuedBy($issuer)
            ->permittedFor(CallbackMiddleware::PATH)
            ->issuedAt($this->time)
            ->canOnlyBeUsedAfter($this->time)
            ->expiresAt($this->time->modify('+1 hour'));

        foreach ($this->payload as $key => $value) {
            if (is_string($key) && $key !== '') {
                $builder = $builder->withClaim($key, $value);
            }
        }

        return $builder->getToken($this->getSigner(), $this->getKey(self::KEY_TYPE_PRIVATE));
    }

    public function getIssuer(): string
    {
        return $this->issuer;
    }

    /**
     * @param array<mixed> $payload
     */
    public function setPayload(array $payload): void
    {
        $this->payload = $payload;
    }

    public function withPayload(string $key, mixed $value): void
    {
        $this->payload[$key] = $value;
    }

    public function verifyToken(string $token): bool
    {
        if ($token === '' || $token === '0') {
            $this->logger?->warning('Given token is empty.');
            return false;
        }

        try {
            $this->token = $this->config->parser()->parse($token);
        } catch (\Exception $exception) {
            /** @extensionScannerIgnoreLine */
            $this->logger?->error($exception->getMessage());
            $this->logger?->warning('Could not parse token.');
            return false;
        }

        if (!$this->config->validator()->validate($this->token, ...$this->config->validationConstraints())) {
            $this->logger?->warning('Token validation failed.');
            return false;
        }
        $this->verified = true;
        return true;
    }

    /**
     * @throws TokenException
     */
    public function getToken(): Token|UnencryptedToken|null
    {
        if (!$this->token instanceof Token) {
            throw new TokenException('No token defined.', 1585905908);
        }

        if (!$this->verified) {
            throw new TokenException('Token needs to be verified before retrieving it.', 1586014609);
        }

        return $this->token;
    }

    public function setIssuer(): void
    {
        if (!ModeUtility::isBackend()) {
            try {
                /** @var PageInformation|null $pageInformation */
                $pageInformation = $GLOBALS['TYPO3_REQUEST']?->getAttribute('frontend.page.information');
                $pageId = $pageInformation?->getId();
                if (!isset($pageId)) {
                    $this->issuer = GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST');
                    return;
                }
                $base = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($pageId)->getBase();

                $this->issuer = sprintf('%s://%s', $base->getScheme(), $base->getHost());
                return;
            } catch (\Exception) {}
        }

        $this->issuer = GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST');
    }

    protected function getSigner(): Signer
    {
        if ($this->configuration->useKeyFiles()) {
            return new Sha256();
        }

        return new Hmac\Sha256();
    }

    protected function getKey(string $type): Key
    {
        if ($this->configuration->useKeyFiles()) {
            if ($type === self::KEY_TYPE_PRIVATE) {
                return InMemory::plainText($this->configuration->getPrivateKeyFile());
            }
            if ($type === self::KEY_TYPE_PUBLIC) {
                return InMemory::plainText($this->configuration->getPublicKeyFile());
            }

            $this->logger?->warning(sprintf('Type %s is not allowed. Using encryption key.', $type));
        }

        return InMemory::plainText($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']);
    }
}
