<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Domain\Model\Auth0;

class Ticket implements Auth0EntityInterface
{
    use Auth0EntityTrait;

    protected $ticket = '';

    public function getTicket(): string
    {
        return $this->ticket;
    }

    public function setTicket(string $ticket)
    {
        $this->ticket = $ticket;
    }
}
