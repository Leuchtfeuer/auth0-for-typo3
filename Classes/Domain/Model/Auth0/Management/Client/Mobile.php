<?php
declare(strict_types = 1);
namespace Bitmotion\Auth0\Domain\Model\Auth0\Management\Client;

/***
 *
 * This file is part of the "Auth0 for TYPO3" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2018 Florian Wessels <f.wessels@bitmotion.de>, Bitmotion GmbH
 *
 ***/

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

    public function setAndroid(array $android): void
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

    public function setIos(array $ios): void
    {
        $this->ios = $ios;
    }
}
