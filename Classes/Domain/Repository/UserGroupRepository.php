<?php

/*
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

namespace Bitmotion\Auth0\Domain\Repository;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class UserGroupRepository
{
    public function getAuth0Roles(string $groupIds): array
    {
        return $this->getAuth0RolesRecursive($groupIds);
    }

    public function findAll(): array
    {
        return $this->getQueryBuilder()
            ->select('*')
            ->from('fe_groups')
            ->execute()
            ->fetchAll();
    }

    public function findAllWithAuth0Role()
    {
        $queryBuilder = $this->getQueryBuilder();

        return $queryBuilder
            ->select('*')
            ->from('fe_groups')
            ->where('auth0_user_group <> ""')
            ->execute()
            ->fetchAll();
    }

    protected function getAuth0RolesRecursive(string $groupIds): array
    {
        $groups = GeneralUtility::intExplode(',', $groupIds);
        $auth0Roles = [];

        foreach ($groups as $groupUid) {
            $group = BackendUtility::getRecord('fe_groups', $groupUid);

            if (!empty($group['auth0_user_group'])) {
                $auth0Roles[$groupUid] = $group['auth0_user_group'];
            }

            if (!empty($group['subgroup'])) {
                $auth0Roles += $this->getAuth0RolesRecursive($group['subgroup']);
            }
        }

        return $auth0Roles;
    }

    protected function getQueryBuilder(): QueryBuilder
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('fe_groups');
    }
}
