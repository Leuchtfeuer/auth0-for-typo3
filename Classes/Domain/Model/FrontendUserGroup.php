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

class FrontendUserGroup extends \TYPO3\CMS\Extbase\Domain\Model\FrontendUserGroup
{
    /**
     * @var string
     */
    protected $auth0UserGroup = '';

    /**
     * @return string $auth0UserGroup
     */
    public function getAuth0UserGroup(): string
    {
        return $this->auth0UserGroup;
    }

    public function setAuth0Id(string $auth0UserGroup)
    {
        $this->auth0UserGroup = $auth0UserGroup;
    }
}
