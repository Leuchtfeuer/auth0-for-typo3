<?php

declare(strict_types=1);

/*
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

namespace Leuchtfeuer\Auth0\Utility;

use Leuchtfeuer\Auth0\Configuration\Auth0Configuration;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\SingletonInterface;

class ParseFuncUtility implements SingletonInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    const NO_AUTH0_VALUE = 'rx63XX7Vq5aCXn4y';

    /**
     * @param array<string, mixed> $user
     * @return mixed|string
     */
    public function updateWithoutParseFunc(string $configurationType, string $auth0FieldName, array $user): mixed
    {
        switch ($configurationType) {
            case Auth0Configuration::CONFIG_TYPE_ROOT:
                $value = $user[$auth0FieldName] ?? null;
                break;

            case Auth0Configuration::CONFIG_TYPE_USER:
                $value = $this->getAuth0ValueRecursive(
                    $user[Auth0Configuration::CONFIG_TYPE_USER], explode
                    ('.', $auth0FieldName)
                );
                break;

            case Auth0Configuration::CONFIG_TYPE_APP:
                $value = $this->getAuth0ValueRecursive(
                    $user[Auth0Configuration::CONFIG_TYPE_APP],
                    explode('.', $auth0FieldName)
                );
                break;

            default:
                $this->logger->warning(sprintf('Invalid configuration type "%s"', $configurationType));
        }

        return $value ?? self::NO_AUTH0_VALUE;
    }

    public function transformValue(string $processing, mixed $value): mixed
    {
        switch ($processing) {
            case 'strtotime':
                $value = strtotime((string)$value);
                break;

            case 'bool':
                $value = (int)(bool)$value;
                break;

            case 'bool-negate':
            case 'negate-bool':
                $value = $value ? 0 : 1;
                break;

            default:
                $this->logger->notice(sprintf('"%s" is not a valid processing function', $processing));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $user
     * @param array<mixed> $properties
     */
    protected function getAuth0ValueRecursive(array $user, array $properties): string
    {
        $property = array_shift($properties);

        if (isset($user[$property])) {
            $value = $user[$property];

            if ($value instanceof \stdClass || (is_array($value) && $value !== [])) {
                return $this->getAuth0ValueRecursive($value, $properties);
            }
        }

        return isset($value) ? (string)$value : self::NO_AUTH0_VALUE;
    }
}
