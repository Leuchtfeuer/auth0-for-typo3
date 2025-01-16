<?php

/*
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) Leuchtfeuer Digital Marketing <dev@Leuchtfeuer.com>
 */

namespace Leuchtfeuer\Auth0\EventListener;

use TYPO3\CMS\Core\SingletonInterface;

class Responsibility implements SingletonInterface
{
    private bool $responsible = false;

    public function isResponsible(): bool
    {
        return $this->responsible;
    }

    public function setResponsible(bool $responsible): void
    {
        $this->responsible = $responsible;
    }
}
