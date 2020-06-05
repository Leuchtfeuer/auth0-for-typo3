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

class Ticket
{
    /**
     * @var string
     */
    protected $ticket;

    /**
     * @return string
     */
    public function getTicket()
    {
        return $this->ticket;
    }

    public function setTicket(string $ticket): void
    {
        $this->ticket = $ticket;
    }
}
