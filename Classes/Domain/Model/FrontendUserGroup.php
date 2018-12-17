<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Domain\Model;

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

/**
 * Class FrontendUserGroup
 */
class FrontendUserGroup extends \TYPO3\CMS\Extbase\Domain\Model\FrontendUserGroup
{
    /**
     * auth0UserGroup
     *
     * @var string
     * @validate NotEmpty
     */
    protected $auth0UserGroup = '';

    /**
     * Returns the auth0UserGroup
     *
     * @return string $auth0UserGroup
     */
    public function getAuth0UserGroup(): string
    {
        return $this->auth0UserGroup;
    }

    /**
     * Sets the auth0UserGroup
     *
     *
     * @return void
     */
    public function setAuth0Id(string $auth0UserGroup)
    {
        $this->auth0UserGroup = $auth0UserGroup;
    }
}
