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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\ApplicationType;

class ModeUtility
{
    public const BACKEND_MODE = 'BE';
    public const UNKONWN_MODE = 'UNKNOWN';

    public static function isBackend(?string $mode = null, ?ServerRequestInterface $request = null): bool
    {
        if (!$mode) {
            $mode = $request instanceof ServerRequestInterface ? self::getModeFromRequest($request) : self::UNKONWN_MODE;
        }

        return $mode === self::BACKEND_MODE;
    }

    public static function getModeFromRequest(ServerRequestInterface $request): string
    {
        return ApplicationType::fromRequest($request)->isBackend()
            ? self::BACKEND_MODE
            : self::UNKONWN_MODE;
    }
}
