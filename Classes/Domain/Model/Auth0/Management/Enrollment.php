<?php
declare(strict_types = 1);
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

class Enrollment
{
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';

    const AUTH_AUTHENTICATOR = 'authenticator';
    const AUTH_GUARDIAN = 'guardian';
    const AUTH_SMS = 'sms';

    /**
     * Enrollment generated id
     *
     * @var string
     */
    protected $id;

    /**
     * Enrollment status
     *
     * @var string
     */
    protected $status;

    /**
     * Enrollment type
     *
     * @var string
     */
    protected $type;

    /**
     * Enrollment name (usually phone number)
     *
     * @var string
     */
    protected $name;

    /**
     * Device identifier (usually phone identifier)
     *
     * @var string
     */
    protected $identity;

    /**
     * Phone number
     *
     * @var string
     */
    protected $phoneNumber;

    /**
     * Enrollment type
     *
     * @var string
     */
    protected $authMethod;

    /**
     * Enrollment date
     *
     * @var string
     */
    protected $enrolledAt;

    /**
     * Last authentication
     *
     * @var string
     */
    protected $lastAuth;

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
    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

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
     * @return string
     */
    public function getIdentity()
    {
        return $this->identity;
    }

    public function setIdentity(string $identity): void
    {
        $this->identity = $identity;
    }

    /**
     * @return string
     */
    public function getPhoneNumber()
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(string $phoneNumber): void
    {
        $this->phoneNumber = $phoneNumber;
    }

    /**
     * @return string
     */
    public function getAuthMethod()
    {
        return $this->authMethod;
    }

    public function setAuthMethod(string $authMethod): void
    {
        $this->authMethod = $authMethod;
    }

    /**
     * @return string
     */
    public function getEnrolledAt()
    {
        return $this->enrolledAt;
    }

    public function setEnrolledAt(string $enrolledAt): void
    {
        $this->enrolledAt = $enrolledAt;
    }

    /**
     * @return string
     */
    public function getLastAuth()
    {
        return $this->lastAuth;
    }

    public function setLastAuth(string $lastAuth): void
    {
        $this->lastAuth = $lastAuth;
    }
}
