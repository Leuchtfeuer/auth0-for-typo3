<?php
declare(strict_types = 1);
namespace Bitmotion\Auth0\Domain\Model\Auth0\Management;

/***
 *
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2018 Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 *
 ***/

class Connection
{
    const STRATEGY_AD = 'ad';
    const STRATEGY_ADFS = 'adfs';
    const STRATEGY_AMAZON = 'amazon';
    const STRATEGY_AOL = 'aol';
    const STRATEGY_AUTH0_ADLAP = 'auth0-adldap';
    const STRATEGY_AUTH0_OIDC = 'auth0-oidc';
    const STRATEGY_AUTH0 = 'auth0';
    const STRATEGY_BAIDU = 'baidu';
    const STRATEGY_BITBUCKET = 'bitbucket';
    const STRATEGY_BITLY = 'bitly';
    const STRATEGY_BOX = 'box';
    const STRATEGY_CUSTOM = 'custom';
    const STRATEGY_DACCOUNT = 'daccount';
    const STRATEGY_DROPBOX = 'dropbox';
    const STRATEGY_DWOLLA = 'dwolla';
    const STRATEGY_EMAIL = 'email';
    const STRATEGY_EVERNOTE_SANDBOX = 'evernote-sandbox';
    const STRATEGY_EVERNOTE = 'evernote';
    const STRATEGY_EXACT = 'exact';
    const STRATEGY_FACEBOOK = 'facebook';
    const STRATEGY_FITBIT = 'fitbit';
    const STRATEGY_FLICKR = 'flickr';
    const STRATEGY_GITHUB = 'github';
    const STRATEGY_GOOGLE_APPS = 'google-apps';
    const STRATEGY_GOOGLE_OAUTH2 = 'google-oauth2';
    const STRATEGY_GUARDIAN = 'guardian';
    const STRATEGY_INSTAGRAM = 'instagram';
    const STRATEGY_IP = 'ip';
    const STRATEGY_LINKEDIN = 'linkedin';
    const STRATEGY_MIICARD = 'miicard';
    const STRATEGY_OAUTH1 = 'oauth1';
    const STRATEGY_OAUTH2 = 'oauth2';
    const STRATEGY_OFFIXE365 = 'office365';
    const STRATEGY_PAYPAL = 'paypal';
    const STRATEGY_PAYPAL_SANDBOX = 'paypal-sandbox';
    const STRATEGY_PINGFEDERATE = 'pingfederate';
    const STRATEGY_PLANNINGCENTER = 'planningcenter';
    const STRATEGY_RENREN = 'renren';
    const STRATEGY_SALESFORCE_COMMUNITY = 'salesforce-community';
    const STRATEGY_SALESFORCE_SANDBOX = 'salesforce-sandbox';
    const STRATEGY_SALESFORCE = 'salesforce';
    const STRATEGY_SAMLP = 'samlp';
    const STRATEGY_SHAREPOINT = 'sharepoint';
    const STRATEGY_SHOPIFY = 'shopify';
    const STRATEGY_SMS = 'sms';
    const STRATEGY_SOUNDCLOUD = 'soundcloud';
    const STRATEGY_THECITY_SANDBOX = 'thecity-sandbox';
    const STRATEGY_THECITY = 'thecity';
    const STRATEGY_THIRTYSEVENSIGNALS = 'thirtysevensignals';
    const STRATEGY_TWITTER = 'twitter';
    const STRATEGY_UNTAPPD = 'untappd';
    const STRATEGY_VKONTAKTE = 'vkontakte';
    const STRATEGY_WAAD = 'waad';
    const STRATEGY_WEIBO = 'weibo';
    const STRATEGY_WINDOWSLIVE = 'windowslive';
    const STRATEGY_WORDPRESS = 'wordpress';
    const STRATEGY_YAHOO = 'yahoo';
    const STRATEGY_YAMMER = 'yammer';
    const STRATEGY_YANDEX = 'yandex';

    /**
     * The name of the connection
     *
     * @var string
     */
    protected $name;

    /**
     * Used to store additional metadata
     *
     * @var array
     */
    protected $options;

    /**
     * The connection's identifier
     *
     * @var string
     */
    protected $id;

    /**
     * The type of the connection, related to the identity provider
     *
     * @var string
     */
    protected $strategy;

    /**
     * Defines the realms for which the connection will be used (ie: email domains). If the array is empty or the property is
     * not specified, the connection name will be added as realm
     *
     * @var string[]
     */
    protected $realms;

    /**
     * True if the connection is domain level
     *
     * @var bool
     */
    protected $isDomainConnection;

    /**
     * Used to store additional metadata
     *
     * @var array
     */
    protected $metadata;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getStrategy()
    {
        return $this->strategy;
    }

    public function setStrategy(string $strategy): void
    {
        $this->strategy = $strategy;
    }

    /**
     * @return string[]
     */
    public function getRealms()
    {
        return $this->realms;
    }

    /**
     * @param string[] $realms
     */
    public function setRealms(array $realms): void
    {
        $this->realms = $realms;
    }

    /**
     * @return bool
     */
    public function isDomainConnection()
    {
        return $this->isDomainConnection;
    }

    public function setIsDomainConnection(bool $isDomainConnection): void
    {
        $this->isDomainConnection = $isDomainConnection;
    }

    /**
     * @return array
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    public function setMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
    }
}
