<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Domain\Model\Auth0;

interface Auth0EntityInterface
{
    public function __construct(array $values);

    public function getFurtherProperties(): array;

    public function setFurtherProperties(array $furtherProperties);
}
