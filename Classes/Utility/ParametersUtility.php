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

class ParametersUtility
{
    /**
     * @return array<string, string>
     */
    public static function transformUrlParameters(?string $url): array
    {
        if ($url === null || $url === '' || $url === '0') {
            return [];
        }

        $additionalParameters = [];

        foreach (explode('&', $url) as $additionalParameter) {
            [$key, $value] = explode('=', $additionalParameter, 2);
            $additionalParameters[trim($key)] = trim($value);
        }

        return $additionalParameters;
    }
}
