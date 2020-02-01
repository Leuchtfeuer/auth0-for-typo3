<?php
declare(strict_types = 1);
namespace Bitmotion\Auth0\Domain\Model\Auth0\Management\Tenant;

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

class PasswordPage
{
    /**
     * true to use the custom change password html, false otherwise (default: false)
     *
     * @var bool
     */
    protected $enabled;

    /**
     * Replace default Guardian page with a custom HTML (Liquid syntax is supported)
     *
     * @var string
     */
    protected $html;

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    /**
     * @return string
     */
    public function getHtml()
    {
        return $this->html;
    }

    public function setHtml(string $html): void
    {
        $this->html = $html;
    }
}
