<?php
declare(strict_types = 1);
namespace Bitmotion\Auth0\Domain\Model\Auth0\Management\Client;

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
