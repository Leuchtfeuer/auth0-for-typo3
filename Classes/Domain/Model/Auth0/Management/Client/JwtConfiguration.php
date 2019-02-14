<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Domain\Model\Auth0\Management\Client;

class JwtConfiguration
{
    const ALG_HS256 = 'HS256';
    const ALG_RS256 = 'RS256';

    /**
     * The amount of seconds the JWT will be valid (affects exp claim)
     *
     * @var int
     */
    protected $lifetimeInSeconds;

    /**
     * true if the client secret is base64 encoded, false otherwise. Defaults to true
     *
     * @var bool
     */
    protected $secretEncoded;

    /**
     * @var string[]
     */
    protected $scopes;

    /**
     * Algorithm uses to sign JWTs
     *
     * @var string
     */
    protected $alg;

    /**
     * @return int
     */
    public function getLifetimeInSeconds()
    {
        return $this->lifetimeInSeconds;
    }

    public function setLifetimeInSeconds(int $lifetimeInSeconds)
    {
        $this->lifetimeInSeconds = $lifetimeInSeconds;
    }

    /**
     * @return bool
     */
    public function isSecretEncoded()
    {
        return $this->secretEncoded;
    }

    public function setSecretEncoded(bool $secretEncoded)
    {
        $this->secretEncoded = $secretEncoded;
    }

    /**
     * @return string[]
     */
    public function getScopes()
    {
        return $this->scopes;
    }

    /**
     * @param string[] $scopes
     */
    public function setScopes(array $scopes)
    {
        $this->scopes = $scopes;
    }

    /**
     * @return string
     */
    public function getAlg()
    {
        return $this->alg;
    }

    public function setAlg(string $alg)
    {
        $this->alg = $alg;
    }
}
