<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function ($extensionKey) {
        // Add content element wizzard to PageTSConfig
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
            '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:auth0/Configuration/TsConfig/Page/ContentElementWizard/setup.tsconfig">'
        );

        // Register icons
        $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
        $iconRegistry->registerIcon(
            'auth0',
            \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
            [
                'source' => 'EXT:auth0/Resources/Public/Icons/auth0.svg',
            ]
        );

        // Connect to signal slots
        $signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
        $signalSlotDispatcher->connect(
            \TYPO3\CMS\Extensionmanager\Utility\InstallUtility::class,
            'afterExtensionInstall',
            \Bitmotion\Auth0\Slots\ConfigurationSlot::class,
            'addCacheHashExcludedParameters'
        );

        // Load extension configuration
        $configuration = new \Bitmotion\Auth0\Domain\Transfer\EmAuth0Configuration();

        if ($configuration->isEnableBackendLogin()) {
            // Register backend hooks
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info']['auth0_loginform'][$extensionKey]
                = \Bitmotion\Auth0\Hooks\PageLayoutViewHook::class . '->getSummary';
            // TODO: Support following hooks for frontend request as well and move to ext_localconf.php file
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['logoff_pre_processing'][$extensionKey]
                = \Bitmotion\Auth0\Hooks\SingleSignOutHook::class . '->isResponsible';
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['logoff_post_processing'][$extensionKey]
                = \Bitmotion\Auth0\Hooks\SingleSignOutHook::class . '->performLogout';

            // Register backend login provider
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['loginProviders'][\Bitmotion\Auth0\LoginProvider\Auth0Provider::LOGIN_PROVIDER] = [
                'provider' => \Bitmotion\Auth0\LoginProvider\Auth0Provider::class,
                'sorting' => 25,
                'icon-class' => 'fa-sign-in',
                'label' => 'LLL:EXT:auth0/Resources/Private/Language/locallang.xlf:backendLogin.switch.label'
            ];
        }
    }, 'auth0'
);
