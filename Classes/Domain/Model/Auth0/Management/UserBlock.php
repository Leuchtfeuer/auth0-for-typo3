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

    public function setBlockedFor(array $blockedFor): void
    {
        $this->blockedFor = $blockedFor;
    }
}
