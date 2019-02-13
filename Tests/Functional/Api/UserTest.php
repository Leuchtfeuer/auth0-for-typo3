<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Tests\Functional\Api;

use Auth0\SDK\Exception\ApiException;
use Bitmotion\Auth0\Api\Management\UserApi;
use Bitmotion\Auth0\Domain\Model\Auth0\Log;
use Bitmotion\Auth0\Domain\Model\Auth0\User;
use Bitmotion\Auth0\Scope;
use Bitmotion\Auth0\Tests\Functional\Auth0TestCase;

class UserTest extends Auth0TestCase
{
    protected $scopes = [
        Scope::USER_READ,
    ];

    /**
     * @test
     * @covers \Bitmotion\Auth0\Utility\ApiUtility::getUserApi
     */
    public function instantiateApi(): UserApi
    {
        $userApi = $this->getApiUtility()->getUserApi(...$this->scopes);
        $this->assertInstanceOf(UserApi::class, $userApi);

        return $userApi;
    }

    /**
     * Find all Users in Auth0
     *
     * @test
     * @depends instantiateApi
     */
    public function listUsers(UserApi $userApi): array
    {
        $users = $userApi->search('*');
        $this->assertNotEmpty($users);

        return $users;
    }

    /**
     * @test
     * @depends instantiateApi
     * @covers \Bitmotion\Auth0\Api\Management\UserApi::search
     */
    public function listUsersWithLimit(UserApi $userApi)
    {
        $users = $userApi->search('*', '', 5);
        $this->assertCount(5, $users);
    }

    /**
     * @test
     * @depends instantiateApi
     * @covers \Bitmotion\Auth0\Api\Management\UserApi::getLog
     */
    public function getLogEntries(UserApi $userApi)
    {
        $logEntries = $userApi->getLog('google-oauth2|117501050803287717769');
        $this->assertIsArray($logEntries);
        $firstEntry = array_shift($logEntries);
        $this->assertInstanceOf(Log::class, $firstEntry);
    }

    /**
     * @test
     * @depends instantiateApi
     * @covers \Bitmotion\Auth0\Api\Management\UserApi::getMetadata
     */
    public function getMetadata(UserApi $userApi)
    {
        $metadata = $userApi->getMetadata($this->getUser()->getUserId(), UserApi::TYPE_USER);
        $this->assertSame('array', gettype($metadata));

        $metadata = $userApi->getMetadata($this->getUser()->getUserId(), UserApi::TYPE_APP);
        $this->assertSame('array', gettype($metadata));

        $metadata = $userApi->getMetadata($this->getUser()->getUserId(), UserApi::TYPE_BOTH);
        $this->assertCount(2, $metadata);
        $this->assertTrue(isset($metadata[UserApi::TYPE_APP]));
        $this->assertTrue(isset($metadata[UserApi::TYPE_USER]));
    }

    /**
     * @test
     * @depends instantiateApi
     * @covers \Bitmotion\Auth0\Api\Management\UserApi::updateMetadata
     */
    public function updateMetadata(UserApi $userApi)
    {
        $time = time();
        $user = $this->getUser();
        $metadata = $user->getUserMetadata();
        $updated = $user->getUpdatedAt();
        $metadata['time'] = $time;

        $this->assertTrue($userApi->updateMetadata($user->getUserId(), $metadata, UserApi::TYPE_USER));

        $user = $userApi->get($user->getUserId());
        $this->assertTrue(isset($user->getUserMetadata()['time']));
        $this->assertEquals($time, $user->getUserMetadata()['time']);
        $this->assertNotEquals($updated, $user->getUpdatedAt());
    }

    /**
     * @test
     * @depends instantiateApi
     * @covers \Bitmotion\Auth0\Api\Management\UserApi::get
     */
    public function getAUser(UserApi $userApi)
    {
        $user = $userApi->get($this->getUser()->getUserId());
        $this->assertInstanceOf(User::class, $user);
        $this->assertNotEmpty($user->getName());

        $user = $userApi->get($this->getUser()->getUserId(), 'name');
        $this->assertEquals($user->getName(), 'John Doe');
        $this->assertNull($user->getEmail());

        $user = $userApi->get($this->getUser()->getUserId(), 'name', false);
        $this->assertNull($user->getName());
        $this->assertEquals($user->getEmail(), 'f.wessels+testing@bitmotion.de');
    }

    /**
     * @test
     * @depends instantiateApi
     * @covers \Bitmotion\Auth0\Api\Management\UserApi::update
     */
    public function updateUser(UserApi $userApi)
    {
        $user = $this->getUser();
        $user->setPassword('EWa5^Eml2*ZN');
        $user = $userApi->update($user, self::CONNECTION_NAME);
        $this->assertInstanceOf(User::class, $user);

        $this->expectException(ApiException::class);
        $user->setPassword(123);
        $userApi->update($user, self::CONNECTION_NAME);
    }

    /**
     * @test
     * @depends instantiateApi
     * @covers \Bitmotion\Auth0\Api\Management\UserApi::getEnrollments
     */
    public function getEnrollments(UserApi $userApi)
    {
        $enrollments = $userApi->getEnrollments($this->getUser()->getUserId());
        $this->assertIsArray($enrollments);
    }

    // TODO: Add test for deleteMultifactorProvider
    // TODO: Add test for unlinkIdentity
    // TODO: Add test for createRecoveryCode
    // TODO: Add test for linkAccountByToken
    // TODO: Add test for linkAccount
}
