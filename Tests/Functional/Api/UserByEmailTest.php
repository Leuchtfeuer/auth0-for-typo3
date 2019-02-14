<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Tests\Functional\Api;

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
        $this->assertInstanceOf(UserByEmailApi::class, $userByEmailApi);

        return $userByEmailApi;
    }

    /**
     * @test
     * @depends instantiateApi
     * @covers \Bitmotion\Auth0\Api\Management\UserByEmailApi::get
     */
    public function get(UserByEmailApi $userByEmailApi)
    {
        $user = $userByEmailApi->get($this->getUser()->getEmail());
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($this->getUser()->getEmail(), $user->getEmail());
    }
}
