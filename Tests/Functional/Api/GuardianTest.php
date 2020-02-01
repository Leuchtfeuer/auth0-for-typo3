<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Tests\Functional\Api;

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

use Bitmotion\Auth0\Api\Management\GuardianApi;
use Bitmotion\Auth0\Scope;
use Bitmotion\Auth0\Tests\Functional\Auth0TestCase;

class GuardianTest extends Auth0TestCase
{
    protected $scopes = [
        Scope::GUARDIAN_FACTOR_READ,
        Scope::GUARDIAN_FACTOR_UPDATE,
    ];

    /**
     * @test
     * @covers \Bitmotion\Auth0\Utility\ApiUtility::getGuardianApi
     */
    public function instantiateApi(): GuardianApi
    {
        $guardianApi = $this->getApiUtility()->getGuardianApi(...$this->scopes);
        $this->assertInstanceOf(GuardianApi::class, $guardianApi);

        return $guardianApi;
    }

    // TODO: implement
}
