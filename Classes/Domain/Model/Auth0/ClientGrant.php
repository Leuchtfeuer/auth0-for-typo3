<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Domain\Model\Auth0;

class ClientGrant
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $clientId;

    /**
     * @var string
     */
    protected $audience;

    /**
     * @var string[]
     */
    protected $scope;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function setClientId(string $clientId): void
    {
        $this->clientId = $clientId;
    }

    public function getAudience(): string
    {
        return $this->audience;
    }

    public function setAudience(string $audience): void
    {
        $this->audience = $audience;
    }

    /**
     * @return string[]
     */
    public function getScope(): array
    {
        return $this->scope;
    }

    /**
     * @param string[] $scope
     */
    public function setScope(array $scope): void
    {
        $this->scope = $scope;
    }
}
