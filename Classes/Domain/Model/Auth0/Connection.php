<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Domain\Model\Auth0;

class Connection implements Auth0EntityInterface
{
    use Auth0EntityTrait;

    protected $name = '';

    protected $options = [];

    protected $id = '';

    protected $strategy = '';

    protected $realms = [];

    protected $isDomainConnection = false;

    protected $metadata = [];

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id)
    {
        $this->id = $id;
    }

    public function getStrategy(): string
    {
        return $this->strategy;
    }

    public function setStrategy(string $strategy)
    {
        $this->strategy = $strategy;
    }

    public function getRealms(): array
    {
        return $this->realms;
    }

    public function setRealms(array $realms)
    {
        $this->realms = $realms;
    }

    public function isDomainConnection(): bool
    {
        return $this->isDomainConnection;
    }

    public function setIsDomainConnection(bool $isDomainConnection)
    {
        $this->isDomainConnection = $isDomainConnection;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setMetadata(array $metadata)
    {
        $this->metadata = $metadata;
    }
}
