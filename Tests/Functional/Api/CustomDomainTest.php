<?php
declare(strict_types = 1);
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

use Bitmotion\Auth0\Api\Management\CustomDomainApi;
use Bitmotion\Auth0\Scope;
use Bitmotion\Auth0\Tests\Functional\Auth0TestCase;

class CustomDomainTest extends Auth0TestCase
{
    protected $scopes = [
        Scope::CUSTOM_DOMAIN_CREATE,
        Scope::CUSTOM_DOMAIN_DELETE,
        Scope::CUSTOM_DOMAIN_READ,
    ];

    /**
     * @test
     * @covers \Bitmotion\Auth0\Utility\ApiUtility::getCustomDomainApi
     */
    public function instantiateApi(): CustomDomainApi
    {
        $customDomainApi = $this->getApiUtility()->getCustomDomainApi(...$this->scopes);
        self::assertInstanceOf(CustomDomainApi::class, $customDomainApi);

        return $customDomainApi;
    }

    // TODO: implement
}
