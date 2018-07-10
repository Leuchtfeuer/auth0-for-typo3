<?php
declare(strict_types=1);

namespace Bitmotion\Auth0\Domain\Model;

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

/**
 * Class FrontendUser
 * @package Bitmotion\Auth0\Domain\Model
 */
class FrontendUser extends \TYPO3\CMS\Extbase\Domain\Model\FrontendUser
{

    /**
     * auth0UserId
     *
     * @var string
     */
    protected $auth0UserId = '';

    /**
     * @var string
     * @validate \Bitmotion\Auth0\Domain\Validator\EmailValidator
     */
    protected $username = '';

    /**
     * @var string
     * @validate \Bitmotion\Auth0\Domain\Validator\PasswordValidator
     */
    protected $password = '';

    /**
     * @var string
     */
    protected $auth0Metadata = '';

    /**
     * @var array
     */
    protected $metadata = [];

    /**
     * @var string
     */
    protected $connection = '';

    /**
     * Returns the auth0UserId
     *
     * @return string
     */
    public function getAuth0UserId(): string
    {
        return $this->auth0UserId;
    }

    /**
     * Sets the auth0UserId
     *
     * @param string $auth0UserId
     */
    public function setAuth0UserId(string $auth0UserId)
    {
        $this->auth0UserId = $auth0UserId;
    }

    /**
     * Returns the auth0Metadata
     *
     * @return array
     */
    public function geAuth0Metadata(): array
    {
        return json_decode($this->auth0Metadata);
    }

    /**
     * Sets the auth0Metadata
     *
     * @param array $auth0Metadata
     */
    public function setAuth0Metadata(array $auth0Metadata)
    {
        $this->auth0Metadata = json_encode($auth0Metadata);
    }

    /**
     * @return array
     * @internal
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * @param array $metadata
     * @internal
     */
    public function setMetadata(array $metadata)
    {
        $this->metadata = $metadata;
    }

    /**
     * @return string
     */
    public function getConnection(): string
    {
        return $this->connection;
    }

    /**
     * @param string $connection
     */
    public function setConnection(string $connection)
    {
        $this->connection = $connection;
    }


}