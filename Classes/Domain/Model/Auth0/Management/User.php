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

class User
{
    /**
     * The user's email
     *
     * @var string
     */
    protected $email;

    /**
     * true if the user's email is verified, false otherwise
     *
     * @var bool
     */
    protected $emailVerified;

    /**
     * The user's username
     *
     * @var string
     */
    protected $username;

    /**
     * The user's phone number (following the E.164 recommendation), only valid for users from SMS connections
     *
     * @var string
     */
    protected $phoneNumber;

    /**
     * true if the user's phone_number is verified, false otherwise, only valid for users from SMS connections
     *
     * @var bool
     */
    protected $phoneVerified;

    /**
     * The user's unique identifier
     *
     * @var string
     */
    protected $userId;

    /**
     * The date when the user was created
     *
     * @var string
     */
    protected $createdAt;

    /**
     * The date when the user was last updated (modified)
     *
     * @var string
     */
    protected $updatedAt;

    /**
     * An array of objects with information about the user's identities. More than one will exists in case accounts are linked
     *
     * @var object[]
     */
    protected $identities;

    /**
     * Used to store additional metadata
     * TODO: Map to object?
     *
     * @var array
     */
    protected $appMetadata;

    /**
     * Used to store additional metadata
     * TODO: Map to object?
     *
     * @var array
     */
    protected $userMetadata;

    /**
     * The user's picture
     *
     * @var string
     */
    protected $picture;

    /**
     * The user's name
     *
     * @var string
     */
    protected $name;

    /**
     * The user's nickname
     *
     * @var string
     */
    protected $nickname;

    /**
     * The list of multifactor providers that the user has enrolled to
     *
     * @var string[]
     */
    protected $multifactor;

    /**
     * The last login IP address
     *
     * @var string
     */
    protected $lastIp;

    /**
     * The last login date for this user
     *
     * @var string
     */
    protected $lastLogin;

    /**
     * The number of logins for this user
     *
     * @var int
     */
    protected $loginsCount;

    /**
     * Indicates whether the user was blocked by an administrator or not
     *
     * @var bool
     */
    protected $blocked;

    /**
     * The user's given name
     *
     * @var string
     */
    protected $givenName;

    /**
     * The user's family name
     *
     * @var string
     */
    protected $familyName;

    /**
     * @var string
     */
    protected $password;

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail(string $email)
    {
        $this->email = $email;
    }

    public function isEmailVerified()
    {
        return $this->emailVerified;
    }

    public function setEmailVerified(bool $emailVerified)
    {
        $this->emailVerified = $emailVerified;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setUsername(string $username)
    {
        $this->username = $username;
    }

    public function getPhoneNumber()
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(string $phoneNumber)
    {
        $this->phoneNumber = $phoneNumber;
    }

    public function isPhoneVerified()
    {
        return $this->phoneVerified;
    }

    public function setPhoneVerified(bool $phoneVerified)
    {
        $this->phoneVerified = $phoneVerified;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function setUserId(string $userId)
    {
        $this->userId = $userId;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setCreatedAt(string $createdAt)
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(string $updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @return object[]
     */
    public function getIdentities()
    {
        return $this->identities;
    }

    /**
     * @param object[] $identities
     */
    public function setIdentities(array $identities)
    {
        $this->identities = $identities;
    }

    public function getAppMetadata()
    {
        return $this->appMetadata;
    }

    public function setAppMetadata(array $appMetadata)
    {
        $this->appMetadata = $appMetadata;
    }

    public function getUserMetadata()
    {
        return $this->userMetadata;
    }

    public function setUserMetadata(array $userMetadata)
    {
        $this->userMetadata = $userMetadata;
    }

    public function getPicture()
    {
        return $this->picture;
    }

    public function setPicture(string $picture)
    {
        $this->picture = $picture;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }

    public function getNickname()
    {
        return $this->nickname;
    }

    public function setNickname(string $nickname)
    {
        $this->nickname = $nickname;
    }

    /**
     * @return string[]
     */
    public function getMultifactor()
    {
        return $this->multifactor;
    }

    /**
     * @param string[] $multifactor
     */
    public function setMultifactor(array $multifactor)
    {
        $this->multifactor = $multifactor;
    }

    public function getLastIp()
    {
        return $this->lastIp;
    }

    public function setLastIp(string $lastIp)
    {
        $this->lastIp = $lastIp;
    }

    public function getLastLogin()
    {
        return $this->lastLogin;
    }

    public function setLastLogin(string $lastLogin)
    {
        $this->lastLogin = $lastLogin;
    }

    public function getLoginsCount()
    {
        return $this->loginsCount;
    }

    public function setLoginsCount(int $loginsCount)
    {
        $this->loginsCount = $loginsCount;
    }

    public function isBlocked()
    {
        return $this->blocked;
    }

    public function setBlocked(bool $blocked)
    {
        $this->blocked = $blocked;
    }

    public function getGivenName()
    {
        return $this->givenName;
    }

    public function setGivenName(string $givenName)
    {
        $this->givenName = $givenName;
    }

    public function getFamilyName()
    {
        return $this->familyName;
    }

    public function setFamilyName(string $familyName)
    {
        $this->familyName = $familyName;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function setPassword($password)
    {
        $this->password = $password;
    }
}
