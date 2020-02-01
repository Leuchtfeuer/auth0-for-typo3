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

use Bitmotion\Auth0\Domain\Model\Auth0\Management\Client\Addon;
use Bitmotion\Auth0\Domain\Model\Auth0\Management\Client\EncryptionKey;
use Bitmotion\Auth0\Domain\Model\Auth0\Management\Client\JwtConfiguration;
use Bitmotion\Auth0\Domain\Model\Auth0\Management\Client\Mobile;

class Client
{
    const CLIENT_SECRET_NONE = 'none';
    const CLIENT_SECRET_POST = 'client_secret_post';
    const CLIENT_SECRET_BASIC = 'client_secret_basic';

    /**
     * The name of the client
     *
     * @var string
     */
    protected $name;

    /**
     * Free text description of the purpose of the Client. (Max character length: 140)
     *
     * @var string
     */
    protected $description;

    /**
     * The id of the client
     *
     * @var string
     */
    protected $clientId;

    /**
     * The client secret, it must not be public
     *
     * @var string
     */
    protected $clientSecret;

    /**
     * The type of application this client represents
     *
     * @var string
     */
    protected $appType;

    /**
     * The URL of the client logo (recommended size: 150x150)
     *
     * @var string
     */
    protected $logoUri;

    /**
     * Whether this client a first party client or not
     *
     * @var bool
     */
    protected $isFirstParty;

    /**
     * Whether this client will conform to strict OIDC specifications
     *
     * @var bool
     */
    protected $oidcConformant;

    /**
     * The URLs that Auth0 can use to as a callback for the client
     *
     * @var string[]
     */
    protected $callbacks;

    /**
     * @var string[]
     */
    protected $allowedOrigins;

    /**
     * A set of URLs that represents valid web origins for use with web message response mode
     *
     * @var string[]
     */
    protected $webOrigins;

    /**
     * @var string[]
     */
    protected $clientAliases;

    /**
     * @var string[]
     */
    protected $allowedClients;

    /**
     * @var string[]
     */
    protected $allowedLogoutUrls;

    /**
     * @var JwtConfiguration
     */
    protected $jwtConfiguration;

    /**
     * Client signing keys.
     *
     * @var string[]
     */
    protected $signingKeys;

    /**
     * @var EncryptionKey
     */
    protected $encryptionKey;

    /**
     * @var bool
     */
    protected $sso;

    /**
     * true to disable Single Sign On, false otherwise (default: false)
     *
     * @var bool
     */
    protected $ssoDisabled;

    /**
     * true if this client can be used to make cross-origin authentication requests, false otherwise (default: false)
     *
     * @var bool
     */
    protected $crossOriginAuth;

    /**
     * Url fo the location in your site where the cross origin verification takes place for the cross-origin auth flow when
     * performing Auth in your own domain instead of Auth0 hosted login page.
     *
     * @var string
     */
    protected $crossOriginLoc;

    /**
     * true if the custom login page is to be used, false otherwise. Defaults to true
     *
     * @var bool
     */
    protected $customLoginPageOn;

    /**
     * @var string
     */
    protected $customLoginPage;

    /**
     * @var string
     */
    protected $customLoginPagePreview;

    /**
     * @var string
     */
    protected $formTemplate;

    /**
     * @var Addon
     */
    protected $addons;

    /**
     * Defines the requested authentication method for the token endpoint. Possible values are 'none'
     * (public client without a client secret), 'client_secret_post' (client uses HTTP POST parameters) or
     * 'client_secret_basic' (client uses HTTP Basic)
     *
     * @var string
     */
    protected $tokenEndpointAuthMethod;

    /**
     * @var array
     */
    protected $clientMetadata;

    /**
     * @var Mobile
     */
    protected $mobile;

    /**
     * @var string[]
     */
    protected $grantTypes;

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
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription(string $description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    public function setClientId(string $clientId)
    {
        $this->clientId = $clientId;
    }

    /**
     * @return string
     */
    public function getClientSecret()
    {
        return $this->clientSecret;
    }

    public function setClientSecret(string $clientSecret)
    {
        $this->clientSecret = $clientSecret;
    }

    /**
     * @return string
     */
    public function getAppType()
    {
        return $this->appType;
    }

    public function setAppType(string $appType)
    {
        $this->appType = $appType;
    }

    /**
     * @return string
     */
    public function getLogoUri()
    {
        return $this->logoUri;
    }

    public function setLogoUri(string $logoUri)
    {
        $this->logoUri = $logoUri;
    }

    /**
     * @return bool
     */
    public function isFirstParty()
    {
        return $this->isFirstParty;
    }

    public function setIsFirstParty(bool $isFirstParty)
    {
        $this->isFirstParty = $isFirstParty;
    }

    /**
     * @return bool
     */
    public function isOidcConformant()
    {
        return $this->oidcConformant;
    }

    public function setOidcConformant(bool $oidcConformant)
    {
        $this->oidcConformant = $oidcConformant;
    }

    /**
     * @return string[]
     */
    public function getCallbacks()
    {
        return $this->callbacks;
    }

    /**
     * @param string[] $callbacks
     */
    public function setCallbacks(array $callbacks)
    {
        $this->callbacks = $callbacks;
    }

    /**
     * @return string[]
     */
    public function getAllowedOrigins()
    {
        return $this->allowedOrigins;
    }

    /**
     * @param string[] $allowedOrigins
     */
    public function setAllowedOrigins(array $allowedOrigins)
    {
        $this->allowedOrigins = $allowedOrigins;
    }

    /**
     * @return string[]
     */
    public function getWebOrigins()
    {
        return $this->webOrigins;
    }

    /**
     * @param string[] $webOrigins
     */
    public function setWebOrigins(array $webOrigins)
    {
        $this->webOrigins = $webOrigins;
    }

    /**
     * @return string[]
     */
    public function getClientAliases()
    {
        return $this->clientAliases;
    }

    /**
     * @param string[] $clientAliases
     */
    public function setClientAliases(array $clientAliases)
    {
        $this->clientAliases = $clientAliases;
    }

    /**
     * @return string[]
     */
    public function getAllowedClients()
    {
        return $this->allowedClients;
    }

    /**
     * @param string[] $allowedClients
     */
    public function setAllowedClients(array $allowedClients)
    {
        $this->allowedClients = $allowedClients;
    }

    /**
     * @return string[]
     */
    public function getAllowedLogoutUrls()
    {
        return $this->allowedLogoutUrls;
    }

    /**
     * @param string[] $allowedLogoutUrls
     */
    public function setAllowedLogoutUrls(array $allowedLogoutUrls)
    {
        $this->allowedLogoutUrls = $allowedLogoutUrls;
    }

    /**
     * @return JwtConfiguration
     */
    public function getJwtConfiguration()
    {
        return $this->jwtConfiguration;
    }

    public function setJwtConfiguration(JwtConfiguration $jwtConfiguration)
    {
        $this->jwtConfiguration = $jwtConfiguration;
    }

    /**
     * @return string[]
     */
    public function getSigningKeys()
    {
        return $this->signingKeys;
    }

    /**
     * @param string[] $signingKeys
     */
    public function setSigningKeys(array $signingKeys)
    {
        $this->signingKeys = $signingKeys;
    }

    /**
     * @return EncryptionKey
     */
    public function getEncryptionKey()
    {
        return $this->encryptionKey;
    }

    public function setEncryptionKey(EncryptionKey $encryptionKey)
    {
        $this->encryptionKey = $encryptionKey;
    }

    /**
     * @return bool
     */
    public function isSso()
    {
        return $this->sso;
    }

    public function setSso(bool $sso)
    {
        $this->sso = $sso;
    }

    /**
     * @return bool
     */
    public function isSsoDisabled()
    {
        return $this->ssoDisabled;
    }

    public function setSsoDisabled(bool $ssoDisabled)
    {
        $this->ssoDisabled = $ssoDisabled;
    }

    /**
     * @return bool
     */
    public function isCrossOriginAuth()
    {
        return $this->crossOriginAuth;
    }

    public function setCrossOriginAuth(bool $crossOriginAuth)
    {
        $this->crossOriginAuth = $crossOriginAuth;
    }

    /**
     * @return string
     */
    public function getCrossOriginLoc()
    {
        return $this->crossOriginLoc;
    }

    public function setCrossOriginLoc(string $crossOriginLoc)
    {
        $this->crossOriginLoc = $crossOriginLoc;
    }

    /**
     * @return bool
     */
    public function isCustomLoginPageOn()
    {
        return $this->customLoginPageOn;
    }

    public function setCustomLoginPageOn(bool $customLoginPageOn)
    {
        $this->customLoginPageOn = $customLoginPageOn;
    }

    /**
     * @return string
     */
    public function getCustomLoginPage()
    {
        return $this->customLoginPage;
    }

    public function setCustomLoginPage(string $customLoginPage)
    {
        $this->customLoginPage = $customLoginPage;
    }

    /**
     * @return string
     */
    public function getCustomLoginPagePreview()
    {
        return $this->customLoginPagePreview;
    }

    public function setCustomLoginPagePreview(string $customLoginPagePreview)
    {
        $this->customLoginPagePreview = $customLoginPagePreview;
    }

    /**
     * @return string
     */
    public function getFormTemplate()
    {
        return $this->formTemplate;
    }

    public function setFormTemplate(string $formTemplate)
    {
        $this->formTemplate = $formTemplate;
    }

    /**
     * @return Addon
     */
    public function getAddons()
    {
        return $this->addons;
    }

    /**
     * @param array $addons
     */
    public function setAddons(Addon $addons)
    {
        $this->addons = $addons;
    }

    /**
     * @return string
     */
    public function getTokenEndpointAuthMethod()
    {
        return $this->tokenEndpointAuthMethod;
    }

    public function setTokenEndpointAuthMethod(string $tokenEndpointAuthMethod)
    {
        $this->tokenEndpointAuthMethod = $tokenEndpointAuthMethod;
    }

    /**
     * @return array
     */
    public function getClientMetadata()
    {
        return $this->clientMetadata;
    }

    public function setClientMetadata(array $clientMetadata)
    {
        $this->clientMetadata = $clientMetadata;
    }

    /**
     * @return Mobile
     */
    public function getMobile()
    {
        return $this->mobile;
    }

    public function setMobile(Mobile $mobile)
    {
        $this->mobile = $mobile;
    }

    /**
     * @return string[]
     */
    public function getGrantTypes()
    {
        return $this->grantTypes;
    }

    /**
     * @param string[] $grantTypes
     */
    public function setGrantTypes(array $grantTypes)
    {
        $this->grantTypes = $grantTypes;
    }
}
