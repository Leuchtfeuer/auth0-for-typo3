<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Domain\Model\Auth0;

class Stat
{
    /**
     * @var \DateTime
     */
    protected $date;

    /**
     * @var int
     */
    protected $logins;

    /**
     * @var int
     */
    protected $signups;

    /**
     * @var int
     */
    protected $leakedPasswords;

    /**
     * @var \DateTime
     */
    protected $createdAt;

    /**
     * @var \DateTime
     */
    protected $updatedAt;

    /**
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param \DateTime $date
     */
    public function setDate(\DateTimeInterface $date)
    {
        $this->date = $date;
    }

    /**
     * @return int
     */
    public function getLogins()
    {
        return $this->logins;
    }

    public function setLogins(int $logins)
    {
        $this->logins = $logins;
    }

    /**
     * @return int
     */
    public function getSignups()
    {
        return $this->signups;
    }

    public function setSignups(int $signups)
    {
        $this->signups = $signups;
    }

    /**
     * @return int
     */
    public function getLeakedPasswords()
    {
        return $this->leakedPasswords;
    }

    public function setLeakedPasswords(int $leakedPasswords)
    {
        $this->leakedPasswords = $leakedPasswords;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }
}
