<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Domain\Model\Auth0\Tenant;

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

    public function setEnabled(bool $enabled)
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

    public function setHtml(string $html)
    {
        $this->html = $html;
    }
}
