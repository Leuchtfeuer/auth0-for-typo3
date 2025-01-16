<?php

/*
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) Leuchtfeuer Digital Marketing <dev@Leuchtfeuer.com>
 */

namespace Leuchtfeuer\Auth0\Domain\Repository\UserGroup;

class BackendUserGroupRepository extends AbstractUserGroupRepository
{
    public const TABLE_NAME = 'be_groups';

    protected function setTableName(): void
    {
        $this->tableName = self::TABLE_NAME;
    }
}
