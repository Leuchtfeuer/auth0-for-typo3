<?php

declare(strict_types=1);

/*
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) Leuchtfeuer Digital Marketing <dev@Leuchtfeuer.com>
 */

namespace Leuchtfeuer\Auth0\Tests\Unit\Utility;

use Leuchtfeuer\Auth0\Configuration\Auth0Configuration;
use Leuchtfeuer\Auth0\Domain\Repository\UserRepository;
use Leuchtfeuer\Auth0\Domain\Repository\UserRepositoryFactory;
use Leuchtfeuer\Auth0\Domain\Transfer\EmAuth0Configuration;
use Leuchtfeuer\Auth0\Utility\UserUtility;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Crypto\Random;

class UserUtilityTest extends TestCase
{
    private const TABLE = 'be_users';

    private PasswordHashFactory&Stub $passwordHashFactory;
    private Random&Stub $random;
    private UserRepositoryFactory $userRepositoryFactory;
    private Auth0Configuration&Stub $auth0Configuration;
    private EmAuth0Configuration&Stub $emConfiguration;

    protected function setUp(): void
    {
        parent::setUp();
        $this->passwordHashFactory = self::createStub(PasswordHashFactory::class);
        $this->random = self::createStub(Random::class);
        $this->userRepositoryFactory = self::createStub(UserRepositoryFactory::class);
        $this->auth0Configuration = self::createStub(Auth0Configuration::class);
        $this->emConfiguration = self::createStub(EmAuth0Configuration::class);
        $this->emConfiguration->method('getUserIdentifier')->willReturn('sub');
    }

    #[Test]
    public function checkIfUserExistsReturnsExistingUserWhenAuth0IdMatches(): void
    {
        $expectedUser = ['uid' => 42, 'auth0_user_id' => 'auth0|42'];

        $repository = $this->createMock(UserRepository::class);
        $repository->expects(self::once())
            ->method('getUserByAuth0Id')
            ->with('auth0|42')
            ->willReturn($expectedUser);

        $this->userRepositoryFactory->method('create')->willReturn($repository);

        $subject = $this->createSubject();

        $result = $subject->checkIfUserExists(self::TABLE, ['sub' => 'auth0|42', 'email' => 'a@b']);

        self::assertSame($expectedUser, $result);
    }

    #[Test]
    public function checkIfUserExistsFallsBackToFindUserWithoutRestrictionsWhenMergeOptionDisabled(): void
    {
        $reactivated = ['uid' => 7, 'auth0_user_id' => 'auth0|gone'];

        // Primary lookup misses, fallback (findUserWithoutRestrictions) hits.
        $primaryRepo = self::createStub(UserRepository::class);
        $primaryRepo->method('getUserByAuth0Id')->willReturn(null);

        $fallbackRepo = self::createStub(UserRepository::class);
        $fallbackRepo->method('getUserByAuth0Id')->willReturn($reactivated);
        $reactivateRepo = self::createStub(UserRepository::class);

        $this->userRepositoryFactory->method('create')
            ->willReturnOnConsecutiveCalls($primaryRepo, $fallbackRepo, $reactivateRepo);

        $this->emConfiguration->method('isMergeUsersByEmailAndUsername')->willReturn(false);

        $subject = $this->createSubject();

        $result = $subject->checkIfUserExists(self::TABLE, ['sub' => 'auth0|gone', 'email' => 'x@y', 'nickname' => 'x']);

        self::assertSame($reactivated, array_intersect_key($reactivated, $result));
    }

    #[Test]
    public function checkIfUserExistsMergesByEmailAndUsernameAndUpdatesAuth0UserId(): void
    {
        $existing = ['uid' => 99, 'auth0_user_id' => 'google-oauth2|old', 'disable' => 0, 'deleted' => 0];

        $primaryRepo = self::createStub(UserRepository::class);
        $primaryRepo->method('getUserByAuth0Id')->willReturn(null);

        $mergeRepo = $this->createMock(UserRepository::class);
        $mergeRepo->expects(self::once())->method('removeRestrictions');
        $mergeRepo->expects(self::once())->method('setOrdering')->with('uid', 'ASC');
        $mergeRepo->expects(self::once())->method('setMaxResults')->with(1);
        $mergeRepo->expects(self::once())
            ->method('getUserByEmailAndUsername')
            ->with('user@example.com', 'old.login')
            ->willReturn($existing);

        $updateRepo = $this->createMock(UserRepository::class);
        $updateRepo->expects(self::once())
            ->method('updateUserByUid')
            ->with(['auth0_user_id' => 'auth0|new'], 99);

        $this->userRepositoryFactory->method('create')
            ->willReturnOnConsecutiveCalls($primaryRepo, $mergeRepo, $updateRepo);

        $this->emConfiguration->method('isMergeUsersByEmailAndUsername')->willReturn(true);
        $this->emConfiguration->method('isReactivateDisabledBackendUsers')->willReturn(false);
        $this->emConfiguration->method('isReactivateDeletedBackendUsers')->willReturn(false);
        $this->auth0Configuration->method('getAuth0PropertyForDatabaseField')->willReturn('nickname');

        $subject = $this->createSubject();

        $result = $subject->checkIfUserExists(self::TABLE, [
            'sub' => 'auth0|new',
            'email' => 'user@example.com',
            'nickname' => 'old.login',
        ]);

        self::assertSame(99, $result['uid']);
        self::assertSame('auth0|new', $result['auth0_user_id']);
    }

    #[Test]
    public function checkIfUserExistsReactivatesDisabledOrDeletedUserWhenSettingsAllow(): void
    {
        $existing = ['uid' => 5, 'auth0_user_id' => 'old', 'disable' => 1, 'deleted' => 1];

        $primaryRepo = self::createStub(UserRepository::class);
        $primaryRepo->method('getUserByAuth0Id')->willReturn(null);

        $mergeRepo = self::createStub(UserRepository::class);
        $mergeRepo->method('getUserByEmailAndUsername')->willReturn($existing);

        $updateRepo = $this->createMock(UserRepository::class);
        $updateRepo->expects(self::once())
            ->method('updateUserByUid')
            ->with(
                self::callback(static fn(array $sets): bool => $sets === [
                    'auth0_user_id' => 'auth0|new',
                    'disable' => 0,
                    'deleted' => 0,
                ]),
                5
            );

        $this->userRepositoryFactory->method('create')
            ->willReturnOnConsecutiveCalls($primaryRepo, $mergeRepo, $updateRepo);

        $this->emConfiguration->method('isMergeUsersByEmailAndUsername')->willReturn(true);
        $this->emConfiguration->method('isReactivateDisabledBackendUsers')->willReturn(true);
        $this->emConfiguration->method('isReactivateDeletedBackendUsers')->willReturn(true);
        $this->auth0Configuration->method('getAuth0PropertyForDatabaseField')->willReturn('nickname');

        $subject = $this->createSubject();
        $subject->checkIfUserExists(self::TABLE, [
            'sub' => 'auth0|new',
            'email' => 'a@b',
            'nickname' => 'foo',
        ]);
    }

    #[Test]
    public function checkIfUserExistsSkipsMergeWhenEmailOrUsernameIsEmpty(): void
    {
        $primaryRepo = self::createStub(UserRepository::class);
        $primaryRepo->method('getUserByAuth0Id')->willReturn(null);

        $fallbackRepo = self::createStub(UserRepository::class);
        $fallbackRepo->method('getUserByAuth0Id')->willReturn(null);

        // Crucial: factory is only called for primary lookup + fallback, NOT for merge attempt.
        $factory = $this->createMock(UserRepositoryFactory::class);
        $factory->expects(self::exactly(2))
            ->method('create')
            ->willReturnOnConsecutiveCalls($primaryRepo, $fallbackRepo);
        $this->userRepositoryFactory = $factory;

        $this->emConfiguration->method('isMergeUsersByEmailAndUsername')->willReturn(true);
        $this->auth0Configuration->method('getAuth0PropertyForDatabaseField')->willReturn('nickname');

        $subject = $this->createSubject();

        // No nickname value -> guard prevents merge attempt.
        $subject->checkIfUserExists(self::TABLE, [
            'sub' => 'auth0|new',
            'email' => 'a@b',
            'nickname' => '',
        ]);
    }

    #[Test]
    public function checkIfUserExistsFallsBackToNicknameWhenYamlMappingMissing(): void
    {
        $existing = ['uid' => 1, 'auth0_user_id' => 'old', 'disable' => 0, 'deleted' => 0];

        $primaryRepo = self::createStub(UserRepository::class);
        $primaryRepo->method('getUserByAuth0Id')->willReturn(null);

        $mergeRepo = $this->createMock(UserRepository::class);
        $mergeRepo->expects(self::once())
            ->method('getUserByEmailAndUsername')
            ->with('a@b', 'fallback.value')
            ->willReturn($existing);

        $updateRepo = self::createStub(UserRepository::class);

        $this->userRepositoryFactory->method('create')
            ->willReturnOnConsecutiveCalls($primaryRepo, $mergeRepo, $updateRepo);

        $this->emConfiguration->method('isMergeUsersByEmailAndUsername')->willReturn(true);
        $this->auth0Configuration->method('getAuth0PropertyForDatabaseField')->willReturn(null);

        $subject = $this->createSubject();
        $subject->checkIfUserExists(self::TABLE, [
            'sub' => 'auth0|new',
            'email' => 'a@b',
            'nickname' => 'fallback.value',
        ]);
    }

    private function createSubject(): UserUtility
    {
        $subject = (new \ReflectionClass(UserUtility::class))->newInstanceWithoutConstructor();
        $reflection = new \ReflectionClass(UserUtility::class);

        foreach ([
            'passwordHashFactory' => $this->passwordHashFactory,
            'random' => $this->random,
            'userRepositoryFactory' => $this->userRepositoryFactory,
            'auth0Configuration' => $this->auth0Configuration,
            'configuration' => $this->emConfiguration,
        ] as $name => $value) {
            $property = $reflection->getProperty($name);
            $property->setValue($subject, $value);
        }

        return $subject;
    }
}
