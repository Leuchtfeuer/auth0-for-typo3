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

use Bitmotion\Auth0\Api\Management\UserByEmailApi;
use Bitmotion\Auth0\Domain\Model\Auth0\Management\User;
use Bitmotion\Auth0\Scope;
use Bitmotion\Auth0\Tests\Functional\Auth0TestCase;

class UserByEmailTest extends Auth0TestCase
{
    protected $scopes = [
        Scope::USER_READ,
    ];

    /**
     * @test
     * @covers \Bitmotion\Auth0\Utility\ApiUtility::getUserByEmailApi
     */
    public function instantiateApi(): UserByEmailApi
    {
        $userByEmailApi = $this->getApiUtility()->getUserByEmailApi(...$this->scopes);
        self::assertInstanceOf(UserByEmailApi::class, $userByEmailApi);

        return $userByEmailApi;
    }

    /**
     * @test
     * @depends instantiateApi
     * @covers \Bitmotion\Auth0\Api\Management\UserByEmailApi::get
     */
    public function get(UserByEmailApi $userByEmailApi): void
    {
        $user = $userByEmailApi->get($this->getUser()->getEmail());
        self::assertInstanceOf(User::class, $user);
        self::assertEquals($this->getUser()->getEmail(), $user->getEmail());
    }
}
