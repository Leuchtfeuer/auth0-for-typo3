<?php
declare(strict_types = 1);
namespace Bitmotion\Auth0\Utility;

/***
 *
 * This file is part of the "Auth0 for TYPO3" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2020 Florian Wessels <f.wessels@bitmotion.de>, Bitmotion GmbH
 *
 ***/

class ParametersUtility
{
    public static function transformUrlParameters(string $url): array
    {
        if (empty($url)) {
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
