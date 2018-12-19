<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Command;

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

use Bitmotion\Auth0\Api\ManagementApi;
use Bitmotion\Auth0\Domain\Model\Application;
use Bitmotion\Auth0\Domain\Model\Dto\EmAuth0Configuration;
use Bitmotion\Auth0\Exception\InvalidApplicationException;
use Bitmotion\Auth0\Utility\ApplicationUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;
use TYPO3\CMS\Extbase\Mvc\Exception\CommandException;

class CleanUpCommandController extends CommandController
{
    /**
     * @var array
     */
    protected $allowedMethods = [
        'disable',
        'delete',
        'deleteIrrevocable',
    ];

    /**
     * @var array
     */
    protected $tableNames = [];

    /**
     * @var array
     */
    protected $users = [];

    /**
     * @var Application
     */
    protected $application = null;

    /**
     * @var string
     */
    protected $method = '';

    /**
     * @throws CommandException
     */
    protected function initialize(string $method)
    {
        // Unknown method
        if (!in_array($method, $this->allowedMethods)) {
            $message = 'Unknown method: %s';
            $this->outputLine(
                '<error>' . $message . '</error>',
                [$method]
            );
            throw new CommandException(sprintf($message, $method));
        }

        $this->initializeBackend();

        $this->method = $method;
        $this->users = $this->getUsers();

        if (empty($this->users)) {
            // Skip: no users found
            $this->outputLine('<info>No users found.</info>');
        }
    }

    /**
     * @throws CommandException
     */
    protected function initializeBackend()
    {
        $configuration = new EmAuth0Configuration();
        if ($configuration->getEnableBackendLogin() === false) {
            $message = 'Backend login is not enabled.';
            $this->outputLine('<error>' . $message . '</error>');
            throw new CommandException($message);
        }

        try {
            $application = ApplicationUtility::getApplication($configuration->getBackendConnection());
            $this->application = $application;
        } catch (InvalidApplicationException $exception) {
            $message = 'No Application found.';
            $this->outputLine('<error>' . $message . '</error>');
            throw new CommandException($message);
        }

        $this->tableNames = [
            'users' => 'be_users',
            'sessions' => 'be_sessions',
        ];
    }

    /**
     * @param string $method "disable", "delete" or "deleteIrrevocable"
     *
     * @throws CommandException
     * @throws \Exception
     */
    public function cleanUpUsersCommand(string $method = 'disable')
    {
        $this->initialize($method);
        $management = GeneralUtility::makeInstance(ManagementApi::class, $this->application);
        $userCount = 0;

        foreach ($this->users as $user) {
            $auth0User = $management->getUserById($user['auth0_user_id']);
            if (isset($auth0User['statusCode']) && $auth0User['statusCode'] === 404) {
                $this->handleUser($user);
                $this->clearSessionData($user);
                $userCount++;
            }
        }

        if ($userCount > 0) {
            $this->outputLine(
                '<info>Removed %i users from %s</info>',
                [$userCount, $this->tableNames['users']]
            );
        } else {
            $this->outputLine(
                '<info>No users removed for table %s.</info>',
                [$this->tableNames['users']]
            );
        }
    }

    protected function getUsers(): array
    {
        $queryBuilder = $this->getQueryBuilder('users');

        if ($this->method === 'delete') {
            $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);
        }

        return $queryBuilder
            ->select('uid', 'auth0_user_id')
            ->from($this->tableNames['users'])
            ->where($queryBuilder->expr()->neq('auth0_user_id', $queryBuilder->createNamedParameter('')))
            ->execute()
            ->fetchAll();
    }

    protected function handleUser(array $user)
    {
        $queryBuilder = $this->getQueryBuilder('users');

        switch ($this->method) {
            // Set disable flag to 1
            case 'disable':
                $queryBuilder->update($this->tableNames['users'])->set('disable', 1);
                break;

            // Set deleted flag to 1
            case 'delete':
                $queryBuilder->update($this->tableNames['users'])->set('deleted', 1);
                break;

            // Remove record from database
            case 'deleteIrrevocable':
                $queryBuilder->delete($this->tableNames['users']);
                break;
        }

        $queryBuilder
            ->where($queryBuilder->expr()->eq('uid', $user['uid']))
            ->execute();
    }

    /**
     * @return \TYPO3\CMS\Core\Database\Query\QueryBuilder
     */
    protected function getQueryBuilder(string $type)
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableNames[$type]);
    }

    protected function clearSessionData(array $user)
    {
        $queryBuilder = $this->getQueryBuilder('sessions');
        $queryBuilder
            ->delete($this->tableNames['sessions'])
            ->where(
                $queryBuilder->expr()->eq(
                    'ses_userid',
                    $queryBuilder->createNamedParameter($user['uid'], \PDO::PARAM_INT)
                )
            )->execute();
    }
}
