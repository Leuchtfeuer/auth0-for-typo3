<?php
declare(strict_types=1);
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
