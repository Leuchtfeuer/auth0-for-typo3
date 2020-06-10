<?php

/*
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

namespace Bitmotion\Auth0\Factory;

class ConfigurationFactory
{
    public function buildProperty(string $auth0Property, string $databaseField, string $processing = 'null'): array
    {
        return [
            'auth0Property' => $auth0Property,
            'databaseField' => $databaseField,
            'readOnly' => false,
            'processing' => $processing,
        ];
    }

    public function buildRoles(string $key, int $defaultFrontendUserGroup, string $adminRole, int $defaultBackendUserGroup): array
    {
        return [
            'default' => [
                'frontend' => $defaultFrontendUserGroup,
                'backend' => $defaultBackendUserGroup,
            ],
            'key' => $key,
            'beAdmin' => $adminRole,
        ];
    }
}
