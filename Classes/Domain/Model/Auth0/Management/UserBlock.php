<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Domain\Model\Auth0\Management;

class UserBlock
{
    /**
     * Array of identifier + ip pairs
     *
     * @var array
     */
    protected $blockedFor;

    /**
     * @return array
     */
    public function getBlockedFor()
    {
        return $this->blockedFor;
    }

    public function setBlockedFor(array $blockedFor)
    {
        $this->blockedFor = $blockedFor;
    }
}
