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

use Bitmotion\Auth0\Domain\Model\Auth0\Management\Tenant\ErrorPage;
use Bitmotion\Auth0\Domain\Model\Auth0\Management\Tenant\Flag;
use Bitmotion\Auth0\Domain\Model\Auth0\Management\Tenant\MfaPage;
use Bitmotion\Auth0\Domain\Model\Auth0\Management\Tenant\PasswordPage;

class Tenant
{
    /**
     * @var PasswordPage
     */
    protected $changePassword;

    /**
     * @var MfaPage
     */
    protected $guradianMfaPage;

    /**
     * Default audience for API Authorization
     *
     * @var string
     */
    protected $defaultAudience;

    /**
     * Name of the connection that will be used for password grants at the token endpoint.
     * Only the following connection types are supported:
     * LDAP, AD, Database Connections, Passwordless, Windows Azure Active Directory, ADFS
     *
     * @var string
     */
    protected $defaultDirectory;

    /**
     * @var ErrorPage
     */
    protected $errorPage;

    /**
     * @var Flag
     */
    protected $flags;

    /**
     * The friendly name of the tenant
     *
     * @var string
     */
    protected $friendlyName;

    /**
     * The URL of the tenant logo (recommended size: 150x150)
     *
     * @var string
     */
    protected $pictureUrl;

    /**
     * User support email
     *
     * @var string
     */
    protected $supportEmail;

    /**
     * User support url
     *
     * @var string
     */
    protected $supportUrl;

    /**
     * A set of URLs that are valid to redirect to after logout from Auth0
     *
     * @var string[]
     */
    protected $allowedLogoutUrls;

    /**
     * Login session lifetime, how long the session will stay valid (unit: hours)
     *
     * @var int
     */
    protected $sessionLifetime;

    /**
     * Force a user to login after they have been inactive for the specified number (unit: hours)
     *
     * @var int
     */
    protected $idleSessionLifetime;

    /**
     * The selected sandbox version to be used for the extensibility environment
     *
     * @var string
     */
    protected $sandboxVersion;

    /**
     * A set of available sandbox versions for the extensibility environment
     *
     * @var string[]
     */
    protected $sandboxVersionsAvailable;

    /**
     * @return PasswordPage
     */
    public function getChangePassword()
    {
        return $this->changePassword;
    }

    public function setChangePassword(PasswordPage $changePassword)
    {
        $this->changePassword = $changePassword;
    }

    /**
     * @return MfaPage
     */
    public function getGuradianMfaPage()
    {
        return $this->guradianMfaPage;
    }

    public function setGuradianMfaPage(MfaPage $guradianMfaPage)
    {
        $this->guradianMfaPage = $guradianMfaPage;
    }

    /**
     * @return string
     */
    public function getDefaultAudience()
    {
        return $this->defaultAudience;
    }

    public function setDefaultAudience(string $defaultAudience)
    {
        $this->defaultAudience = $defaultAudience;
    }

    /**
     * @return string
     */
    public function getDefaultDirectory()
    {
        return $this->defaultDirectory;
    }

    public function setDefaultDirectory(string $defaultDirectory)
    {
        $this->defaultDirectory = $defaultDirectory;
    }

    /**
     * @return ErrorPage
     */
    public function getErrorPage()
    {
        return $this->errorPage;
    }

    public function setErrorPage(ErrorPage $errorPage)
    {
        $this->errorPage = $errorPage;
    }

    /**
     * @return Flag
     */
    public function getFlags()
    {
        return $this->flags;
    }

    public function setFlags(Flag $flags)
    {
        $this->flags = $flags;
    }

    /**
     * @return string
     */
    public function getFriendlyName()
    {
        return $this->friendlyName;
    }

    public function setFriendlyName(string $friendlyName)
    {
        $this->friendlyName = $friendlyName;
    }

    /**
     * @return string
     */
    public function getPictureUrl()
    {
        return $this->pictureUrl;
    }

    public function setPictureUrl(string $pictureUrl)
    {
        $this->pictureUrl = $pictureUrl;
    }

    /**
     * @return string
     */
    public function getSupportEmail()
    {
        return $this->supportEmail;
    }

    public function setSupportEmail(string $supportEmail)
    {
        $this->supportEmail = $supportEmail;
    }

    /**
     * @return string
     */
    public function getSupportUrl()
    {
        return $this->supportUrl;
    }

    public function setSupportUrl(string $supportUrl)
    {
        $this->supportUrl = $supportUrl;
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
     * @return int
     */
    public function getSessionLifetime()
    {
        return $this->sessionLifetime;
    }

    public function setSessionLifetime(int $sessionLifetime)
    {
        $this->sessionLifetime = $sessionLifetime;
    }

    /**
     * @return int
     */
    public function getIdleSessionLifetime()
    {
        return $this->idleSessionLifetime;
    }

    public function setIdleSessionLifetime(int $idleSessionLifetime)
    {
        $this->idleSessionLifetime = $idleSessionLifetime;
    }

    /**
     * @return string
     */
    public function getSandboxVersion()
    {
        return $this->sandboxVersion;
    }

    public function setSandboxVersion(string $sandboxVersion)
    {
        $this->sandboxVersion = $sandboxVersion;
    }

    /**
     * @return string[]
     */
    public function getSandboxVersionsAvailable()
    {
        return $this->sandboxVersionsAvailable;
    }

    /**
     * @param string[] $sandboxVersionsAvailable
     */
    public function setSandboxVersionsAvailable(array $sandboxVersionsAvailable)
    {
        $this->sandboxVersionsAvailable = $sandboxVersionsAvailable;
    }
}
