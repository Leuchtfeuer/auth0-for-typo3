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
use Bitmotion\Auth0\Domain\Repository\ApplicationRepository;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;
use TYPO3\CMS\Extbase\Mvc\Exception\CommandException;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/**
 * Class CleanUpCommandController
 * @package Bitmotion\Auth0\Command
 */
class CleanUpCommandController extends CommandController
{
    /**
     * @var array
     */
    protected $allowedContexts = [
        'BE',
        'FE'
    ];

    /**
     * @var array
     */
    protected $allowedMethods = [
        'disable',
        'delete'
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
     * @param string $context
     * @param string $method
     *
     * @throws CommandException
     */
    protected function initialize(string $context, string $method)
    {
        // Unknown context
        if (!in_array($context, $this->allowedContexts)) {
            $message = 'Unknown context: %s';
            $this->outputLine(
                '<error>' . $message . '</error>',
                [$context]
            );
            throw new CommandException(sprintf($message, $context));
        }

        // Unknown method
        if (!in_array($method, $this->allowedMethods)) {
            $message = 'Unknown method: %s';
            $this->outputLine(
                '<error>' . $message . '</error>',
                [$method]
            );
            throw new CommandException(sprintf($message, $method));
        }

        if ($context === 'BE') {
            $this->initializeBackend();
        } else {
            $this->initializeFrontend();
        }

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

        $repository = $this->objectManager->get(ApplicationRepository::class);
        $application = $repository->findByUid($configuration->getBackendConnection());

        if (!$application instanceof Application) {
            $message = 'No Application found.';
            $this->outputLine('<error>' . $message . '</error>');
            throw new CommandException($message);
        }

        $this->application = $application;

        $this->tableNames = [
            'users' => 'be_users',
            'sessions' => 'be_sessions'
        ];
    }

    /**
     * @throws CommandException
     */
    protected function initializeFrontend()
    {
        $this->tableNames = [
            'users' => 'fe_users',
            'sessions' => 'fe_sessions'
        ];

        // TODO: Get all applications and remove users by application
        throw new CommandException('FE Stuff not implemented properly.');
    }

    /**
     * @param string $context "BE" for backend, "FE" for frontend (not supported right now)
     * @param string $method "disable" or "delete"
     *
     * @throws CommandException
     * @throws \Exception
     */
    public function cleanUpUsersCommand(string $context = 'BE', string $method = 'disable')
    {
        $this->initialize($context, $method);
        $management = GeneralUtility::makeInstance(ManagementApi::class, $this->application);
        $userCount = 0;

        foreach ($this->users as $user) {
            $auth0User = $management->getUserById($user['auth0_user_id']);
            if (isset($auth0User['statusCode']) && $auth0User['statusCode'] === 404) {
                $this->handleUser($user);
                $this->clearSessionData($user);
                // TODO: Clear Further data? Logs, ...?
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

    /**
     * @return array
     */
    protected function getUsers(): array
    {
        $queryBuilder = $this->getQueryBuilder('users');

        return $queryBuilder
            ->select('uid', 'auth0_user_id')
            ->from($this->tableNames['users'])
            ->where($queryBuilder->expr()->neq('auth0_user_id', $queryBuilder->createNamedParameter('')))
            ->execute()
            ->fetchAll();
    }

    /**
     * @param array $user
     */
    protected function handleUser(array $user)
    {
        $queryBuilder = $this->getQueryBuilder('users');

        if ($this->method === 'disable') {
            // Set disable flag to 1
            $queryBuilder
                ->update($this->tableNames['users'])
                ->set('disable', 1);
        } elseif ($this->method === 'delete') {
            // Remove user from database
            $queryBuilder
                ->delete($this->tableNames['users']);
        }

        $queryBuilder
            ->where($queryBuilder->expr()->eq('uid', $user['uid']))
            ->execute();
    }

    /**
     * @param string $type
     *
     * @return \TYPO3\CMS\Core\Database\Query\QueryBuilder
     */
    protected function getQueryBuilder(string $type)
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableNames[$type]);
    }

    /**
     * @param array $user
     */
    protected function clearSessionData(array $user)
    {
        $queryBuilder = $this->getQueryBuilder('session');
        $queryBuilder
            ->delete($this->tableNames['session'])
            ->where(
                $queryBuilder->expr()->eq(
                    'ses_userid',
                    $queryBuilder->createNamedParameter($user['uid'], \PDO::PARAM_INT)
                )
            )->execute();
    }

}