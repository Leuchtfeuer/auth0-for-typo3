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

namespace Leuchtfeuer\Auth0\Utility;

use Auth0\SDK\Auth0;
use Auth0\SDK\Utility\HttpResponse;
use GuzzleHttp\Utils;
use Leuchtfeuer\Auth0\Configuration\Auth0Configuration;
use Leuchtfeuer\Auth0\Domain\Repository\ApplicationRepository;
use Leuchtfeuer\Auth0\Domain\Repository\UserRepository;
use Leuchtfeuer\Auth0\Domain\Transfer\EmAuth0Configuration;
use Leuchtfeuer\Auth0\Utility\Database\UpdateUtility;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class UserUtility implements SingletonInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected EmAuth0Configuration $configuration;

    public function __construct()
    {
        $this->configuration = new EmAuth0Configuration();
    }

    public function checkIfUserExists(string $tableName, array $userInfo): array
    {
        $auth0UserId = $userInfo[$this->configuration->getUserIdentifier()] ?? '';
        $userRepository = GeneralUtility::makeInstance(UserRepository::class, $tableName);
        $user = $userRepository->getUserByAuth0Id($auth0UserId);

        if ($user === null && $this->configuration->isMergeUsersByEmailAndUsername()) {
            $user = $this->findExistingUserByEmailAndUsername($tableName, $userInfo);
        }

        return $user ?? $this->findUserWithoutRestrictions($tableName, $auth0UserId);
    }

    protected function findExistingUserByEmailAndUsername(string $tableName, array $userInfo): ?array
    {
        $email = (string)($userInfo['email'] ?? '');
        $username = $this->resolveUserInfoValue(
            $tableName,
            'username',
            $userInfo,
            ['configurationType' => Auth0Configuration::CONFIG_TYPE_ROOT, 'auth0Property' => 'nickname']
        );
        $newAuth0UserId = (string)($userInfo[$this->configuration->getUserIdentifier()] ?? '');

        if ($email === '' || $username === '' || $newAuth0UserId === '') {
            $this->logger->notice(sprintf(
                'Skip merge by email+username: missing values (email="%s", username="%s", auth0_user_id="%s").',
                $email,
                $username,
                $newAuth0UserId
            ));
            return null;
        }

        $userRepository = GeneralUtility::makeInstance(UserRepository::class, $tableName);
        $userRepository->removeRestrictions();
        $userRepository->setOrdering('uid', 'ASC');
        $userRepository->setMaxResults(1);
        $user = $userRepository->getUserByEmailAndUsername($email, $username);

        if ($user === null) {
            return null;
        }

        $sets = ['auth0_user_id' => $newAuth0UserId];

        $isFrontend = ($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
            && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isFrontend();

        if ($isFrontend) {
            if ($this->configuration->isReactivateDisabledFrontendUsers() && (int)($user['disable'] ?? 0) === 1) {
                $sets['disable'] = 0;
            }
            if ($this->configuration->isReactivateDeletedFrontendUsers() && (int)($user['deleted'] ?? 0) === 1) {
                $sets['deleted'] = 0;
            }
        } else {
            if ($this->configuration->isReactivateDisabledBackendUsers() && (int)($user['disable'] ?? 0) === 1) {
                $sets['disable'] = 0;
            }
            if ($this->configuration->isReactivateDeletedBackendUsers() && (int)($user['deleted'] ?? 0) === 1) {
                $sets['deleted'] = 0;
            }
        }

        $updateRepository = GeneralUtility::makeInstance(UserRepository::class, $tableName);
        $updateRepository->updateUserByUid($sets, (int)$user['uid']);

        $this->logger->notice(sprintf(
            'Merged existing user (uid=%d) with new auth0_user_id "%s" via email+username match.',
            (int)$user['uid'],
            $newAuth0UserId
        ));

        return array_merge($user, $sets);
    }

    /**
     * Resolves the value that UpdateUtility would have written into the given
     * TYPO3 column for this userInfo payload. Walks every YAML mapping that
     * targets the field, in declaration order, and returns the last
     * non-empty resolution — mirroring UpdateUtility::mapUserData, where each
     * subsequent mapping overwrites the previous. A first-match lookup would
     * silently disagree with the persisted value whenever two mappings point
     * at the same column (e.g. root.nickname *and* user_metadata.login_name
     * → username), reintroducing the duplicate-user bug this method exists
     * to prevent.
     *
     * The fallback mapping is only consulted when the YAML declares nothing
     * at all for the field.
     */
    protected function resolveUserInfoValue(
        string $tableName,
        string $databaseField,
        array $userInfo,
        ?array $fallback = null
    ): string {
        $auth0Configuration = GeneralUtility::makeInstance(Auth0Configuration::class);
        $mappings = $auth0Configuration->getAuth0MappingsForDatabaseField($tableName, $databaseField);
        if ($mappings === [] && $fallback !== null) {
            $mappings = [[
                'configurationType' => (string)$fallback['configurationType'],
                'auth0Property' => (string)$fallback['auth0Property'],
                'processing' => (string)($fallback['processing'] ?? ''),
            ]];
        }
        if ($mappings === []) {
            return '';
        }

        $parseFuncUtility = GeneralUtility::makeInstance(ParseFuncUtility::class);
        $resolvedValue = null;
        foreach ($mappings as $mapping) {
            $value = $parseFuncUtility->updateWithoutParseFunc(
                $mapping['configurationType'],
                $mapping['auth0Property'],
                $userInfo
            );
            if ($value === ParseFuncUtility::NO_AUTH0_VALUE) {
                continue;
            }
            $processing = $mapping['processing'];
            if ($processing !== '' && $processing !== 'null') {
                $value = $parseFuncUtility->transformValue($processing, $value);
            }
            $resolvedValue = $value;
        }

        return $resolvedValue === null ? '' : (string)$resolvedValue;
    }

    protected function findUserWithoutRestrictions(string $tableName, string $auth0UserId): array
    {
        $this->logger->notice('Try to find user without restrictions.');
        $userRepository = GeneralUtility::makeInstance(UserRepository::class, $tableName);
        $userRepository->removeRestrictions();
        $userRepository->setOrdering('uid', 'DESC');
        $userRepository->setMaxResults(1);
        $user = $userRepository->getUserByAuth0Id($auth0UserId);

        if (!empty($user)) {
            $userRepository = GeneralUtility::makeInstance(UserRepository::class, $tableName);
            $userRepository->updateUserByUid(['disable' => 0, 'deleted' => 0], (int)$user['uid']);

            $this->logger->notice(sprintf('Reactivated user with ID %s.', $user['uid']));
        }

        return $user ?? [];
    }

    /**
     * @throws InvalidPasswordHashException
     */
    public function insertUser(string $tableName, $user): void
    {
        switch ($tableName) {
            case 'fe_users':
                $this->insertFeUser($tableName, $user);
                break;
            case 'be_users':
                $this->insertBeUser($tableName, $user);
                break;
            default:
                $this->logger->error(sprintf('"%s" is not a valid table name.', $tableName));
        }
    }

    public function enrichManagementUser(array $managementUser): array
    {
        $managementUser[$this->configuration->getUserIdentifier()] = $managementUser['user_id'];
        return $managementUser;
    }

    /**
     * Inserts a new frontend user
     *
     * @throws InvalidPasswordHashException
     */
    public function insertFeUser(string $tableName, array $user): void
    {
        $values = $this->getTcaDefaults($tableName);
        $userIdentifier = $this->configuration->getUserIdentifier();

        ArrayUtility::mergeRecursiveWithOverrule($values, [
            'pid' => $this->configuration->getUserStoragePage(),
            'tstamp' => time(),
            'username' => $user['email'] ?? $user[$userIdentifier],
            'password' => $this->getPassword('FE'),
            'email' => $user['email'] ?? '',
            'crdate' => time(),
            'auth0_user_id' => $user[$userIdentifier],
            'auth0_metadata' => Utils::jsonEncode($user['user_metadata'] ?? ''),
        ]);

        GeneralUtility::makeInstance(UserRepository::class, $tableName)->insertUser($values);
    }

    /**
     * Inserts a new backend user
     *
     * @throws InvalidPasswordHashException
     */
    public function insertBeUser(string $tableName, array $user): void
    {
        $values = $this->getTcaDefaults($tableName);
        $userIdentifier = $this->configuration->getUserIdentifier();

        ArrayUtility::mergeRecursiveWithOverrule($values, [
            'pid' => 0,
            'tstamp' => time(),
            'username' => $user['email'] ?? $user[$userIdentifier],
            'password' => $this->getPassword('BE'),
            'email' => $user['email'] ?? '',
            'crdate' => time(),
            'auth0_user_id' => $user[$userIdentifier],
        ]);

        GeneralUtility::makeInstance(UserRepository::class, $tableName)->insertUser($values);
    }

    protected function getTcaDefaults(string $tableName): array
    {
        $defaults = [];
        $columns = $GLOBALS['TCA'][$tableName]['columns'] ?? [];

        foreach ($columns as $fieldName => $field) {
            if (isset($field['config']['default'])) {
                $defaults[$fieldName] = $field['config']['default'];
            }
        }

        return $defaults;
    }

    /**
     * @throws InvalidPasswordHashException
     */
    protected function getPassword(string $mode): string
    {
        $saltFactory = GeneralUtility::makeInstance(PasswordHashFactory::class)->getDefaultHashInstance($mode);
        $password = GeneralUtility::makeInstance(Random::class)->generateRandomHexString(50);

        return $saltFactory->getHashedPassword($password);
    }

    public function updateUser(Auth0 $auth0, int $application): void
    {
        try {
            $this->logger->notice('Try to update user.');
            if ($auth0->exchange()) {
                $user = $auth0->getUser();
            } else {
                $user = null;
            }

            $application = BackendUtility::getRecord(ApplicationRepository::TABLE_NAME, $application, 'api, uid');

            if ((bool)$application['api'] === true && $user) {
                $response = $auth0->management()->users()->get($user[$this->configuration->getUserIdentifier()]);
                if (HttpResponse::wasSuccessful($response)) {
                    $userUtility = GeneralUtility::makeInstance(UserUtility::class);
                    $user =  $userUtility->enrichManagementUser(HttpResponse::decodeContent($response));
                }
            }

            // Update existing user on every login
            $updateUtility = GeneralUtility::makeInstance(UpdateUtility::class, 'fe_users', $user);
            $updateUtility->updateUser();
            $updateUtility->updateGroups();
        } catch (\Exception $exception) {
            $this->logger->warning(
                sprintf(
                    'Updating user failed with following message: %s (%s)',
                    $exception->getMessage(),
                    $exception->getCode()
                )
            );
        }
    }

    public function setLastUsedApplication(string $auth0UserId, int $application): void
    {
        $userRepository = GeneralUtility::makeInstance(UserRepository::class, 'fe_users');
        $userRepository->updateUserByAuth0Id(['auth0_last_application' => $application], $auth0UserId);
    }
}
