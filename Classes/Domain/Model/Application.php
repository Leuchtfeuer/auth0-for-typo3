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

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title)
    {
        $this->title = $title;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id)
    {
        $this->id = $id;
    }

    public function getSecret(): string
    {
        return $this->secret;
    }

    public function setSecret(string $secret)
    {
        $this->secret = $secret;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function setDomain(string $domain)
    {
        $this->domain = $domain;
    }

    public function getAudience(): string
    {
        // Audience have to look like this: api/v2/
        return trim($this->audience, '/') . '/';
    }

    public function setAudience(string $audience)
    {
        $this->audience = $audience;
    }

    public function isSingleLogOut(): bool
    {
        return $this->singleLogOut;
    }

    public function setSingleLogOut(bool $singleLogOut): void
    {
        $this->singleLogOut = $singleLogOut;
    }
}
