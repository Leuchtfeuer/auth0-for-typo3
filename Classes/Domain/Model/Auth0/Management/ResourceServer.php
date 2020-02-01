<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Domain\Model\Auth0\Management;

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

use Bitmotion\Auth0\Domain\Model\Auth0\Management\ResourceServer\Scope;

class ResourceServer
{
    /**
     * The identifier of the resource server.
     *
     * @var string
     */
    protected $identifier;

    /**
     * @var Scope[]
     */
    protected $scopes;

    /**
     * The algorithm used to sign tokens
     *
     * @var string
     */
    protected $signingAlg;

    /**
     * The secret used to sign tokens when using symmetric algorithms
     *
     * @var string
     */
    protected $signingSecret;

    /**
     * Allows issuance of refresh tokens for this entity
     *
     * @var bool
     */
    protected $allowOfflineAccess;

    /**
     * Flag this entity as capable of skipping consent
     *
     * @var bool
     */
    protected $skipConsentForVerifiableFirstPartyClients;

    /**
     * The amount of time (in seconds) that the token will be valid after being issued
     *
     * @var int
     */
    protected $tokenLifetime;

    /**
     * The amount of time (in seconds) that the token will be valid after being issued from browser based flows. Value cannot
     * be larger than token_lifetime.
     *
     * @var int
     */
    protected $tokenLifetimeForWeb;

    /**
     * The ID of the resource server
     *
     * @var string
     */
    protected $id;

    /**
     * A friendly name for the resource server.
     *
     * @var string
     */
    protected $name;

    /**
     * Whether this API is a system API
     *
     * @var bool
     */
    protected $isSystem;

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * @return Scope[]
     */
    public function getScopes()
    {
        return $this->scopes;
    }

    /**
     * @param Scope[] $scopes
     */
    public function setScopes(array $scopes)
    {
        $this->scopes = $scopes;
    }

    /**
     * @return string
     */
    public function getSigningAlg()
    {
        return $this->signingAlg;
    }

    public function setSigningAlg(string $signingAlg)
    {
        $this->signingAlg = $signingAlg;
    }

    /**
     * @return string
     */
    public function getSigningSecret()
    {
        return $this->signingSecret;
    }

    public function setSigningSecret(string $signingSecret)
    {
        $this->signingSecret = $signingSecret;
    }

    /**
     * @return bool
     */
    public function isAllowOfflineAccess()
    {
        return $this->allowOfflineAccess;
    }

    public function setAllowOfflineAccess(bool $allowOfflineAccess)
    {
        $this->allowOfflineAccess = $allowOfflineAccess;
    }

    /**
     * @return bool
     */
    public function isSkipConsentForVerifiableFirstPartyClients()
    {
        return $this->skipConsentForVerifiableFirstPartyClients;
    }

    public function setSkipConsentForVerifiableFirstPartyClients(bool $skipConsentForVerifiableFirstPartyClients)
    {
        $this->skipConsentForVerifiableFirstPartyClients = $skipConsentForVerifiableFirstPartyClients;
    }

    /**
     * @return int
     */
    public function getTokenLifetime()
    {
        return $this->tokenLifetime;
    }

    public function setTokenLifetime(int $tokenLifetime)
    {
        $this->tokenLifetime = $tokenLifetime;
    }

    /**
     * @return int
     */
    public function getTokenLifetimeForWeb()
    {
        return $this->tokenLifetimeForWeb;
    }

    /**
     * @param string $tokenLifetimeForWeb
     */
    public function setTokenLifetimeForWeb(int $tokenLifetimeForWeb)
    {
        $this->tokenLifetimeForWeb = $tokenLifetimeForWeb;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    public function setId(string $id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return bool
     */
    public function isSystem()
    {
        return $this->isSystem;
    }

    public function setIsSystem(bool $isSystem)
    {
        $this->isSystem = $isSystem;
    }
}
