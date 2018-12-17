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

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Class Application
 */
class Application extends AbstractEntity
{
    /**
     * title
     *
     * @var string
     * @validate NotEmpty
     */
    protected $title = '';

    /**
     * id
     *
     * @var string
     * @validate NotEmpty
     */
    protected $id = '';

    /**
     * secret
     *
     * @var string
     * @validate NotEmpty
     */
    protected $secret = '';

    /**
     * domain
     *
     * @var string
     * @validate NotEmpty
     */
    protected $domain = '';

    /**
     * audience
     *
     * @var string
     * @validate NotEmpty
     */
    protected $audience = '';

    /**
     * Returns the title
     *
     * @return string $title
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Sets the title
     *
     * @return void
     */
    public function setTitle(string $title)
    {
        $this->title = $title;
    }

    /**
     * Returns the id
     *
     * @return string $id
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Sets the id
     *
     * @return void
     */
    public function setId(string $id)
    {
        $this->id = $id;
    }

    /**
     * Returns the secret
     *
     * @return string $secret
     */
    public function getSecret(): string
    {
        return $this->secret;
    }

    /**
     * Sets the secret
     *
     * @return void
     */
    public function setSecret(string $secret)
    {
        $this->secret = $secret;
    }

    /**
     * Returns the domain
     *
     * @return string $domain
     */
    public function getDomain(): string
    {
        return $this->domain;
    }

    /**
     * Sets the domain
     *
     * @return void
     */
    public function setDomain(string $domain)
    {
        $this->domain = $domain;
    }

    /**
     * Returns the audience
     *
     * @return string $audience
     */
    public function getAudience(): string
    {
        // Audience have to look like this: api/v2/
        return trim($this->audience, '/') . '/';
    }

    /**
     * Sets the audience
     *
     * @return void
     */
    public function setAudience(string $audience)
    {
        $this->audience = $audience;
    }
}
