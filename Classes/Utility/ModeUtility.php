<?php

/*
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

namespace Leuchtfeuer\Auth0\Utility;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ModeUtility
{
    public const BACKEND_MODE = 'BE';
    public const UNKONWN_MODE = 'UNKNOWN';

    public static function isBackend(?string $mode=null): bool
    {
        if (!$mode) {
            $mode = self::getModeFromRequest();
        }

        return $mode === self::BACKEND_MODE;
    }

    public static function getModeFromRequest(): string
    {
        $request = self::getRequest();
        return $request instanceof ServerRequestInterface && ApplicationType::fromRequest($request)->isBackend()
            ? self::BACKEND_MODE
            : self::UNKONWN_MODE;
    }

    public static function isTYPO3V12(): bool
    {
        return version_compare(
            GeneralUtility::makeInstance(Typo3Version::class)->getVersion(),
            '12.0',
            '>='
        );
    }

    private static function getRequest(): ?ServerRequestInterface
    {
        return $GLOBALS['TYPO3_REQUEST'] ?? null;
    }
}
