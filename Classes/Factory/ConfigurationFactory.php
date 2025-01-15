<?php

/*
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

namespace Leuchtfeuer\Auth0\Factory;

class ConfigurationFactory
{
    /**
     * @return array{auth0Property: string, databaseField: string, readOnly: false, processing: string}
     */
    public function buildProperty(string $auth0Property, string $databaseField, string $processing = 'null'): array
    {
        return [
            'auth0Property' => $auth0Property,
            'databaseField' => $databaseField,
            'readOnly' => false,
            'processing' => $processing,
        ];
    }

    /**
     * @return array{default: array{backend: int}, key: string, beAdmin: string}
     */
    public function buildRoles(string $key, string $adminRole, int $defaultBackendUserGroup): array
    {
        return [
            'default' => [
                'backend' => $defaultBackendUserGroup,
            ],
            'key' => $key,
            'beAdmin' => $adminRole,
        ];
    }
}
