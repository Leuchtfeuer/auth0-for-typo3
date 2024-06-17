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
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Hmac;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Token\Plain;
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

class TokenUtility implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    const KEY_TYPE_PRIVATE = 'private';

    const KEY_TYPE_PUBLIC = 'public';

    const ENVIRONMENT_FRONTEND = 'FE';

    const ENVIRONMENT_BACKEND = 'BE';

    protected EmAuth0Configuration $configuration;

    protected DateTimeImmutable $time;

    protected string $issuer = '';

    protected array $payload = [];

    protected ?Token $token;

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
        $contraints[] = new IssuedBy($this->getIssuer());
        $contraints[] = new PermittedFor(CallbackMiddleware::PATH);
        $contraints[] = new SignedWith($this->getSigner(), $this->getKey(self::KEY_TYPE_PUBLIC));
        return $contraints;
    }

    public function buildToken(): Plain
    {
        $builder = $this->config->builder();
        $builder->issuedBy($this->getIssuer());
        $builder->permittedFor(CallbackMiddleware::PATH);
        $builder->issuedAt($this->time);
        $builder->canOnlyBeUsedAfter($this->time);
        $builder->expiresAt($this->time->modify('+1 hour'));

        foreach ($this->payload as $key => $value) {
            $builder->withClaim($key, $value);
        }

        return $builder->getToken($this->getSigner(), $this->getKey(self::KEY_TYPE_PRIVATE));
    }

    public function getIssuer(): string
    {
        return $this->issuer;
    }

    public function setPayload(array $payload): void
    {
        $this->payload = $payload;
    }

    public function withPayload(string $key, $value): void
    {
        $this->payload[$key] = $value;
    }

    public function verifyToken(string $token): bool
    {
        if (empty($token)) {
            $this->logger->warning('Given token is empty.');
            return false;
        }

        try {
            $this->token = $this->config->parser()->parse($token);
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            $this->logger->warning('Could not parse token.');
            return false;
        }

        if (!$this->config->validator()->validate($this->token, ...$this->config->validationConstraints())) {
            $this->logger->warning('Token validation failed.');
            return false;
        }
        $this->verified = true;
        return true;
    }

    /**
     * @throws TokenException
     */
    public function getToken(): ?Token
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
                if (!isset($GLOBALS['TSFE'])) {
                    $this->issuer = GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST');
                    return;
                }

                $pageId = (int)$GLOBALS['TSFE']->id;
                $base = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($pageId)->getBase();

                if ($base->getScheme() !== null) {
                    $this->issuer = sprintf('%s://%s', $base->getScheme(), $base->getHost());
                    return;
                }

                // Base of site configuration might be "/" so we have to retrieve the domain from the ENV
                $this->issuer = GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST');
            } catch (\Exception $exception) {
                $this->issuer = GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST');
            }

            return;
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

            $this->logger->warning(sprintf('Type %s is not allowed. Using encryption key.', $type));
        }

        return InMemory::plainText($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']);
    }
}
