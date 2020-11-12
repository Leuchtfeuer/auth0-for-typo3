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

/**
 * @deprecated This object is deprecated and will be removed in version 4. If you need this object, you can simply copy this class
 *             into your extension.
 */
class FrontendUser extends \TYPO3\CMS\Extbase\Domain\Model\FrontendUser
{
    /**
     * @var string
     */
    protected $auth0UserId = '';

    /**
     * @var string
     */
    protected $username = '';

    /**
     * @var string
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

    public function getAuth0UserId(): string
    {
        return $this->auth0UserId;
    }

    public function setAuth0UserId(string $auth0UserId): void
    {
        $this->auth0UserId = $auth0UserId;
    }

    public function geAuth0Metadata(): array
    {
        return \GuzzleHttp\json_decode($this->auth0Metadata);
    }

    public function setAuth0Metadata(array $auth0Metadata): void
    {
        $this->auth0Metadata = \GuzzleHttp\json_encode($auth0Metadata);
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function getConnection(): string
    {
        return $this->connection;
    }

    public function setConnection(string $connection): void
    {
        $this->connection = $connection;
    }
}
