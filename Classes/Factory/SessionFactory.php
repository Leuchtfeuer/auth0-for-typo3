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

use Bitmotion\Auth0\Store\SessionStore;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\EnvironmentService;

class SessionFactory
{
    public const SESSION_PREFIX_BACKEND = 'BE';

    public const SESSION_PREFIX_FRONTEND = 'FE';

    public function getSessionStoreForApplication(int $application = 0)
    {
        // TODO: Add Application to session store
        $storeName = sprintf('%s%s_', SessionStore::BASE_NAME, $this->getContext());

        return new SessionStore($storeName);
    }

    public function getContext(): string
    {
        $environmentService = GeneralUtility::makeInstance(EnvironmentService::class);

        if ($environmentService->isEnvironmentInBackendMode()) {
            return self::SESSION_PREFIX_BACKEND;
        }

        // TODO: Maybe add an dedicated identifier per Site
        // $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
        // $site = $siteFinder->getSiteByPageId($GLOBALS['TSFE']->id);

        // return sprintf('%s_%s', self::SESSION_PREFIX_FRONTEND, $site->getIdentifier());

        return '';
    }
}
