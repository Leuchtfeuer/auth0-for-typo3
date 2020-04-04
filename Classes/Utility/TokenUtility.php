<?php
declare(strict_types = 1);
namespace Bitmotion\Auth0\Utility;

/***
 *
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2020 Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 *
 ***/

use Bitmotion\Auth0\Domain\Transfer\EmAuth0Configuration;
use Bitmotion\Auth0\Exception\TokenException;
use Bitmotion\Auth0\Middleware\CallbackMiddleware;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Hmac;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Rsa;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\ValidationData;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\EnvironmentService;

class TokenUtility implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    const KEY_TYPE_PRIVATE = 'private';

    const KEY_TYPE_PUBLIC = 'public';

    const ENVIRONMENT_FRONTEND = 'FE';

    const ENVIRONMENT_BACKEND = 'BE';

    protected $configuration;

    protected $time = 0;

    protected $issuer = '';

    protected $payload = [];

    protected $token;

    protected $verified = false;

    public function __construct()
    {
        $this->configuration = new EmAuth0Configuration();
        $this->time = time();
        $this->setIssuer();
    }

    public function buildToken()
    {
        $builder = new Builder();
        $builder->issuedBy($this->getIssuer());
        $builder->permittedFor(CallbackMiddleware::PATH);

        $this->addTime($builder);
        $this->addClaims($builder);

        return $builder->getToken($this->getSigner(), $this->getKey());
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
            $token = (new Parser())->parse($token);
            $this->token = $token;
        } catch (\Exception $exception) {
            $this->logger->warning('Could not parse token.');
            return false;
        }

        if (!$token->validate($this->getValidationData())) {
            $this->logger->warning('Token validation failed.');
            return false;
        }

        if (!$token->verify($this->getSigner(), $this->getKey('public'))) {
            $this->logger->warning('Token verification failed.');
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

    protected function setIssuer(): void
    {
        $environmentService = GeneralUtility::makeInstance(EnvironmentService::class);

        if ($environmentService->isEnvironmentInFrontendMode()) {
            try {
                $pageId = (int)$GLOBALS['TSFE']->id;
                $base = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($pageId)->getBase();
                $this->issuer = sprintf('%s://%s', $base->getScheme(), $base->getHost());
            } catch (SiteNotFoundException $exception) {
                $this->issuer = GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST');
            }
            $this->withPayload('environment', self::ENVIRONMENT_FRONTEND);
        } elseif ($environmentService->isEnvironmentInBackendMode()) {
            $this->issuer = GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST');
            $this->withPayload('environment', self::ENVIRONMENT_BACKEND);
        }
    }

    protected function addTime(Builder &$builder): void
    {
        $builder->issuedAt($this->time);
        $builder->canOnlyBeUsedAfter($this->time);
        $builder->expiresAt($this->time + 3600);
    }

    protected function addClaims(Builder &$builder): void
    {
        foreach ($this->payload as $key => $value) {
            $builder->withClaim($key, $value);
        }
    }

    protected function getSigner(): Signer
    {
        if ($this->configuration->useKeyFiles()) {
            return new Rsa\Sha256();
        }

        return new Hmac\Sha256();
    }

    protected function getKey(string $type = 'private'): Key
    {
        if ($this->configuration->useKeyFiles()) {
            if ($type === self::KEY_TYPE_PRIVATE) {
                return new Key($this->configuration->getPrivateKeyFile());
            }
            if ($type === self::KEY_TYPE_PUBLIC) {
                return new Key($this->configuration->getPublicKeyFile());
            }

            $this->logger->warning(sprintf('Type %s is not allowed. Using encryption key.', $type));
        }

        return new Key($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']);
    }

    protected function getValidationData(): ValidationData
    {
        $validationData = new ValidationData();
        $validationData->setIssuer($this->getIssuer());
        $validationData->setAudience(CallbackMiddleware::PATH);

        return $validationData;
    }
}
