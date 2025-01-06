<?php

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
