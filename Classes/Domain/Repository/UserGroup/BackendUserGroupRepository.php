<?php

/*
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

namespace Bitmotion\Auth0\Domain\Repository\UserGroup;

class BackendUserGroupRepository extends AbstractUserGroupRepository
{
    const TABLE_NAME = 'be_groups';

    protected function setTableName(): void
    {
        $this->tableName = self::TABLE_NAME;
    }
}
