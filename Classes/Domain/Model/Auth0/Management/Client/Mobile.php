<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Domain\Model\Auth0\Management\Client;

class Mobile
{
    /**
     * Configuration related to Android native apps
     *
     * @var array
     */
    protected $android;

    /**
     * Configuration related to iOS native apps
     *
     * @var array
     */
    protected $ios;

    /**
     * @return array
     */
    public function getAndroid()
    {
        return $this->android;
    }

    public function setAndroid(array $android)
    {
        $this->android = $android;
    }

    /**
     * @return array
     */
    public function getIos()
    {
        return $this->ios;
    }

    public function setIos(array $ios)
    {
        $this->ios = $ios;
    }
}
