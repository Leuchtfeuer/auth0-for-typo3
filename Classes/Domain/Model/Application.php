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

namespace Leuchtfeuer\Auth0\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class Application extends AbstractEntity
{
    public const ALG_HS256 = 'HS256';
    public const ALG_RS256 = 'RS256';

    /**
     * @var string
     */
    protected $title = '';

    /**
     * @var string
     */
    protected $id = '';

    /**
     * @var string
     */
    protected $secret = '';

    /**
     * @var string
     */
    protected $domain = '';

    /**
     * @var string
     */
    protected $audience = '';

    protected bool $singleLogOut = false;

    protected bool $api = true;

    protected string $signatureAlgorithm = self::ALG_RS256;

    /**
     * @var bool
     */
    protected $customDomain = false;

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getClientId(): string
    {
        /** @extensionScannerIgnoreLine */
        return $this->id;
    }

    public function setId(string $id): self
    {
        /** @extensionScannerIgnoreLine */
        $this->id = $id;

        return $this;
    }

    public function getClientSecret(): string
    {
        return $this->secret;
    }

    public function setSecret(string $secret): self
    {
        $this->secret = $secret;

        return $this;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function setDomain(string $domain): self
    {
        $this->domain = $domain;

        return $this;
    }

    public function getFullDomain(): string
    {
        return sprintf('https://%s', rtrim($this->domain, '/'));
    }

    public function getManagementTokenDomain(): string
    {
        return sprintf('https://%s/oauth/token', rtrim($this->domain, '/'));
    }

    public function getAudience(bool $asFullDomain = false): string
    {
        if ($asFullDomain && !$this->isCustomDomain()) {
            return sprintf('https://%s/%s', $this->domain, $this->audience);
        }

        return $this->audience;
    }

    public function setAudience(string $audience): self
    {
        $this->audience = trim($audience, '/') . '/';

        return $this;
    }

    public function getApiBasePath(): string
    {
        return sprintf('/%s/', trim(parse_url($this->getAudience(true), PHP_URL_PATH), '/'));
    }

    public function isSingleLogOut(): bool
    {
        return $this->singleLogOut;
    }

    public function setSingleLogOut(bool $singleLogOut): self
    {
        $this->singleLogOut = $singleLogOut;

        return $this;
    }

    public function getSignatureAlgorithm(): string
    {
        return $this->signatureAlgorithm;
    }

    public function setSignatureAlgorithm(string $signatureAlgorithm): self
    {
        $this->signatureAlgorithm = $signatureAlgorithm;

        return $this;
    }

    public function isCustomDomain(): bool
    {
        return filter_var($this->audience, FILTER_VALIDATE_URL) !== false;
    }

    public function hasApi(): bool
    {
        return $this->api;
    }

    public function setApi(bool $api): self
    {
        $this->api = $api;

        return $this;
    }

    public static function fromArray(array $data): self
    {
        return (new self())
            ->setTitle($data['title'])
            ->setId($data['id'])
            ->setSecret($data['secret'])
            ->setDomain($data['domain'])
            ->setAudience($data['audience'])
            ->setSingleLogOut((bool)$data['single_log_out'])
            ->setSignatureAlgorithm($data['signature_algorithm'] ?? '')
            ->setApi((bool)$data['api']);
    }
}
