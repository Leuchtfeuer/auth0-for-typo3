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

use Bitmotion\Auth0\Api\Management\UserBlockApi;
use Bitmotion\Auth0\Domain\Model\Auth0\Management\UserBlock;
use Bitmotion\Auth0\Scope;
use Bitmotion\Auth0\Tests\Functional\Auth0TestCase;

class UserBlockTest extends Auth0TestCase
{
    protected $scopes = [
     Scope::USER_READ,
     Scope::USER_UPDATE,
 ];

    protected $userApi;

    public function setUp(): void
    {
        parent::setUp();

        $user = $this->getUser();
        $user->setBlocked(true);

        $this->userApi = $this->getApiUtility()->getUserApi(...$this->scopes);
        $this->userApi->update($user);
    }

    /**
     * @test
     * @covers \Bitmotion\Auth0\Utility\ApiUtility::getUserBlockApi
     */
    public function instantiateApi(): UserBlockApi
    {
        $userBlockApi = $this->getApiUtility()->getUserBlockApi(...$this->scopes);
        self::assertInstanceOf(UserBlockApi::class, $userBlockApi);

        return $userBlockApi;
    }

    /**
     * @test
     * @depends instantiateApi
     * @covers \Bitmotion\Auth0\Api\Management\UserBlockApi::unblock
     */
    public function unblockUser(UserBlockApi $userBlockApi): void
    {
        self::assertTrue($this->getUser()->isBlocked());
        $success = $userBlockApi->unblockUser($this->getUser());
        self::assertTrue($success);
//        $user = $this->userApi->get($this->getUser()->getUserId());
        // TODO: Users blocked by Admin or API call (setBlocked) can not be unblocked this way
        // $this->assertFalse($user->isBlocked());
    }

    /**
     * @test
     * @depends instantiateApi
     * @covers \Bitmotion\Auth0\Api\Management\UserBlockApi::getBlocks
     */
    public function getBlocks(UserBlockApi $userBlockApi): void
    {
        $blocks = $userBlockApi->getBlocks($this->getUser()->getEmail());
        self::assertInstanceOf(UserBlock::class, $blocks);
    }

    /**
     * @test
     * @depends instantiateApi
     * @covers \Bitmotion\Auth0\Api\Management\UserBlockApi::getUserBlocks
     */
    public function getUserBlocks(UserBlockApi $userBlockApi): void
    {
        $blocks = $userBlockApi->getUserBlocks($this->getUser());
        self::assertInstanceOf(UserBlock::class, $blocks);
    }

    /**
     * @test
     * @depends instantiateApi
     * @covers \Bitmotion\Auth0\Api\Management\UserBlockApi::unblock
     */
    public function unblock(UserBlockApi $userBlockApi): void
    {
        $success = $userBlockApi->unblock($this->getUser()->getEmail());
        self::assertTrue($success);
    }
}
