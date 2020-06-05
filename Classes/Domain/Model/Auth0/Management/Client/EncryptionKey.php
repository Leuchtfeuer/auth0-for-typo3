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

namespace Bitmotion\Auth0\Domain\Model\Auth0\Management\Client;

class EncryptionKey
{
    /**
     * @var string
     */
    protected $pub;

    /**
     * @var string
     */
    protected $cert;

    /**
     * @var
     */
    protected $subject;

    /**
     * @return string
     */
    public function getPub()
    {
        return $this->pub;
    }

    public function setPub(string $pub): void
    {
        $this->pub = $pub;
    }

    /**
     * @return string
     */
    public function getCert()
    {
        return $this->cert;
    }

    public function setCert(string $cert): void
    {
        $this->cert = $cert;
    }

    public function getSubject()
    {
        return $this->subject;
    }

    public function setSubject($subject): void
    {
        $this->subject = $subject;
    }
}
