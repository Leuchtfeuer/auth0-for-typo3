<?php

declare(strict_types=1);

/*
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

namespace Bitmotion\Auth0\Domain\Model\Auth0\Management\Tenant;

class ErrorPage
{
    /**
     * Replace default error page with a custom HTML (Liquid syntax is supported)
     *
     * @var string
     */
    protected $html;

    /**
     * true to show link to log as part of the default error page, false otherwise (default: true)
     *
     * @var bool
     */
    protected $showLogLink;

    /**
     * Redirect to specified url instead of show the default error page
     *
     * @var string
     */
    protected $url;

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

    /**
     * @return bool
     */
    public function isShowLogLink()
    {
        return $this->showLogLink;
    }

    public function setShowLogLink(bool $showLogLink): void
    {
        $this->showLogLink = $showLogLink;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }
}
