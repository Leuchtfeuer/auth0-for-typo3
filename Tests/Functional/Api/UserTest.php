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

use Auth0\SDK\Exception\ApiException;
use Bitmotion\Auth0\Api\Management\UserApi;
use Bitmotion\Auth0\Domain\Model\Auth0\Management\Log;
use Bitmotion\Auth0\Domain\Model\Auth0\Management\User;
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
        self::assertInstanceOf(UserApi::class, $userApi);

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
        self::assertNotEmpty($users);

        return $users;
    }

    /**
     * @test
     * @depends instantiateApi
     * @covers \Bitmotion\Auth0\Api\Management\UserApi::search
     */
    public function listUsersWithLimit(UserApi $userApi): void
    {
        $users = $userApi->search('*', '', 5);
        self::assertCount(5, $users);
    }

    /**
     * @test
     * @depends instantiateApi
     * @covers \Bitmotion\Auth0\Api\Management\UserApi::getLog
     */
    public function getLogEntries(UserApi $userApi): void
    {
        $logEntries = $userApi->getLog('google-oauth2|117501050803287717769');
        self::assertIsArray($logEntries);
        $firstEntry = array_shift($logEntries);
        self::assertInstanceOf(Log::class, $firstEntry);
    }

    /**
     * @test
     * @depends instantiateApi
     * @covers \Bitmotion\Auth0\Api\Management\UserApi::getMetadata
     */
    public function getMetadata(UserApi $userApi): void
    {
        $metadata = $userApi->getMetadata($this->getUser()->getUserId(), UserApi::TYPE_USER);
        self::assertSame('array', gettype($metadata));

        $metadata = $userApi->getMetadata($this->getUser()->getUserId(), UserApi::TYPE_APP);
        self::assertSame('array', gettype($metadata));

        $metadata = $userApi->getMetadata($this->getUser()->getUserId(), UserApi::TYPE_BOTH);
        self::assertCount(2, $metadata);
        self::assertTrue(isset($metadata[UserApi::TYPE_APP]));
        self::assertTrue(isset($metadata[UserApi::TYPE_USER]));
    }

    /**
     * @test
     * @depends instantiateApi
     * @covers \Bitmotion\Auth0\Api\Management\UserApi::updateMetadata
     */
    public function updateMetadata(UserApi $userApi): void
    {
        $time = time();
        $user = $this->getUser();
        $metadata = $user->getUserMetadata();
        $updated = $user->getUpdatedAt();
        $metadata['time'] = $time;

        self::assertTrue($userApi->updateMetadata($user, $metadata, UserApi::TYPE_USER));

        $user = $userApi->get($user->getUserId());
        self::assertTrue(isset($user->getUserMetadata()['time']));
        self::assertEquals($time, $user->getUserMetadata()['time']);
        self::assertNotEquals($updated, $user->getUpdatedAt());
    }

    /**
     * @test
     * @depends instantiateApi
     * @covers \Bitmotion\Auth0\Api\Management\UserApi::get
     */
    public function getAUser(UserApi $userApi): void
    {
        $user = $userApi->get($this->getUser()->getUserId());
        self::assertInstanceOf(User::class, $user);
        self::assertNotEmpty($user->getName());

        $user = $userApi->get($this->getUser()->getUserId(), 'name');
        self::assertEquals($user->getName(), 'John Doe');
        self::assertNull($user->getEmail());

        $user = $userApi->get($this->getUser()->getUserId(), 'name', false);
        self::assertNull($user->getName());
        self::assertEquals($user->getEmail(), $this->getUser()->getEmail());
    }

    /**
     * @test
     * @depends instantiateApi
     * @covers \Bitmotion\Auth0\Api\Management\UserApi::update
     */
    public function updateUser(UserApi $userApi): void
    {
        $user = $this->getUser();
        $user->setPassword('EWa5^Eml2*ZN');
        $user = $userApi->update($user, self::CONNECTION_NAME);
        self::assertInstanceOf(User::class, $user);

        $this->expectException(ApiException::class);
        $user->setPassword(123);
        $userApi->update($user, self::CONNECTION_NAME);
    }

    /**
     * @test
     * @depends instantiateApi
     * @covers \Bitmotion\Auth0\Api\Management\UserApi::getEnrollments
     */
    public function getEnrollments(UserApi $userApi): void
    {
        $enrollments = $userApi->getEnrollments($this->getUser()->getUserId());
        self::assertIsArray($enrollments);
    }

    // TODO: Add test for deleteMultifactorProvider
    // TODO: Add test for unlinkIdentity
    // TODO: Add test for createRecoveryCode
    // TODO: Add test for linkAccountByToken
    // TODO: Add test for linkAccount
}
