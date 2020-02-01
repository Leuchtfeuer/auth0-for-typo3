<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Domain\Model\Auth0\Management\Client;

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

    public function setPub(string $pub)
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

    public function setCert(string $cert)
    {
        $this->cert = $cert;
    }

    public function getSubject()
    {
        return $this->subject;
    }

    public function setSubject($subject)
    {
        $this->subject = $subject;
    }
}
