<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Domain\Model\Auth0\Management;

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

    public function setBlockedFor(array $blockedFor): void
    {
        $this->blockedFor = $blockedFor;
    }
}
