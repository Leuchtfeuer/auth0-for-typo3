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
    public const FRONTEND_MODE = 'FE';

    public static function isBackend(?string $mode=null): bool
    {
        if (!$mode) {
            $mode = self::getModeFromRequest();
        }

        return $mode && $mode === self::BACKEND_MODE;
    }

    public static function getModeFromRequest(): string
    {
        return ($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
            && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isFrontend()
            ? self::FRONTEND_MODE
            : self::BACKEND_MODE;
    }

    public static function isTYPO3V12(): bool
    {
        return version_compare(GeneralUtility::makeInstance(Typo3Version::class)->getVersion(), '12.0', '>=');
    }
}
