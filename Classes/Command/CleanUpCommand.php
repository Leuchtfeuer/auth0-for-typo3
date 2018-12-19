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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CleanUpCommand extends Command
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
    protected $tableNames = [
        'users' => 'be_users',
        'sessions' => 'be_sessions',
    ];

    /**
     * @var array
     */
    protected $users = [];

    /**
     * @var Application
     */
    protected $application;

    /**
     * @var string
     */
    protected $method = '';

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var EmAuth0Configuration
     */
    protected $configuration;

    protected function configure()
    {
        $this->addArgument('method', InputArgument::REQUIRED, '"disable", "delete" or "deleteIrrevocable"');
    }

    /**
     * @throws \TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException
     * @throws \TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException
     * @throws \Exception
     * @return int|void|null
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        if (!$this->isInputValid($input)) {
            $output->writeln(sprintf('<error>Unknown method: %s</error>', $input->getArgument('method')));

            return;
        }

        if (!$this->isBackendLoginEnabled()) {
            $output->writeln(sprintf('<error>Backend login is not enabled.</error>'));

            return;
        }

        if (!$this->setAuth0Application()) {
            $output->writeln('<error>No Application found.</error>');

            return;
        }

        if ($this->setUsers()) {
            $output->writeln('<info>No users found.</info>');
        }

        $userCount = $this->updateUsers();

        if ($userCount > 0) {
            $output->writeln(sprintf('<info>Removed %i users from %s</info>', $userCount, $this->tableNames['users']));
        } else {
            $output->writeln(sprintf('<info>No users removed for table %s.</info>', $this->tableNames['users']));
        }
    }

    protected function isInputValid(InputInterface $input): bool
    {
        if (!in_array($input->getArgument('method'), $this->allowedMethods)) {
            return false;
        }

        $this->method = $input->getArgument('method');

        return true;
    }

    /**
     * @throws \TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException
     * @throws \TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException
     */
    protected function isBackendLoginEnabled()
    {
        $configuration = new EmAuth0Configuration();

        if ($configuration->getEnableBackendLogin() === false) {
            return false;
        }

        $this->configuration = $configuration;

        return true;
    }

    protected function setAuth0Application(): bool
    {
        try {
            $application = ApplicationUtility::getApplication($this->configuration->getBackendConnection());
            $this->application = $application;
        } catch (InvalidApplicationException $exception) {
            return false;
        }

        return true;
    }

    protected function setUsers(): bool
    {
        $queryBuilder = $this->getQueryBuilder('users');

        if ($this->method === 'delete') {
            $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);
        }

        $this->users = $queryBuilder
            ->select('uid', 'auth0_user_id')
            ->from($this->tableNames['users'])
            ->where($queryBuilder->expr()->neq('auth0_user_id', $queryBuilder->createNamedParameter('')))
            ->execute()
            ->fetchAll();

        return !empty($this->users);
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

    protected function getQueryBuilder(string $type): QueryBuilder
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

    /**
     * @throws \Exception
     */
    protected function updateUsers(): int
    {
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

        return $userCount;
    }
}
