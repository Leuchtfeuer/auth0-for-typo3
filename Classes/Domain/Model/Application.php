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

namespace Bitmotion\Auth0\Domain\Model;

use Bitmotion\Auth0\Domain\Model\Auth0\Management\Client\JwtConfiguration;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class Application extends AbstractEntity
{
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
     * @var bool
     */
    protected $secretBase64Encoded = false;

    /**
     * @var string
     */
    protected $domain = '';

    /**
     * @var string
     */
    protected $audience = '';

    /**
     * @var bool
     */
    protected $singleLogOut = false;

    /**
     * @var bool
     */
    protected $api = true;

    /**
     * @var string
     */
    protected $signatureAlgorithm = JwtConfiguration::ALG_RS256;

    /**
     * @var bool
     */
    protected $customDomain = false;

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getClientId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getClientSecret(): string
    {
        return $this->secret;
    }

    public function setSecret(string $secret): void
    {
        $this->secret = $secret;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function setDomain(string $domain): void
    {
        $this->domain = $domain;
    }

    public function getFullDomain()
    {
        return sprintf('https://%s', rtrim($this->domain, '/'));
    }

    public function getAudience(bool $asFullDomain = false): string
    {
        if ($asFullDomain && !$this->isCustomDomain()) {
            return sprintf('https://%s/%s', $this->domain, $this->audience);
        }

        return $this->audience;
    }

    public function setAudience(string $audience): void
    {
        $this->audience = trim($audience, '/') . '/';
    }

    public function getApiBasePath()
    {
        return sprintf('/%s/', trim(parse_url($this->getAudience(true), PHP_URL_PATH), '/'));
    }

    public function isSingleLogOut(): bool
    {
        return $this->singleLogOut;
    }

    public function setSingleLogOut(bool $singleLogOut): void
    {
        $this->singleLogOut = $singleLogOut;
    }

    public function isSecretBase64Encoded(): bool
    {
        return $this->secretBase64Encoded;
    }

    public function setSecretBase64Encoded(bool $secretBase64Encoded): void
    {
        $this->secretBase64Encoded = $secretBase64Encoded;
    }

    public function getSignatureAlgorithm(): string
    {
        return $this->signatureAlgorithm;
    }

    public function setSignatureAlgorithm(string $signatureAlgorithm): void
    {
        $this->signatureAlgorithm = $signatureAlgorithm;
    }

    public function isCustomDomain(): bool
    {
        return filter_var($this->audience, FILTER_VALIDATE_URL) !== false;
    }

    public function hasApi(): bool
    {
        return $this->api;
    }

    public function setApi(bool $api): void
    {
        $this->api = $api;
    }
}
