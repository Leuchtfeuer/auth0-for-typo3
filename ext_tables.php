<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function ($extensionKey) {
        // Add content element wizard to PageTSconfig
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('
            <INCLUDE_TYPOSCRIPT: source="FILE:EXT:auth0/Configuration/TSconfig/Page/ContentElementWizard/setup.tsconfig">
        ');

        // Register icons - deprecated will move to Configuration/Icons.php in future
        $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
        $iconRegistry->registerIcon(
            'auth0',
            \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
            [
                'source' => 'EXT:auth0/Resources/Public/Icons/auth0.svg',
            ]
        );

        // Load extension configuration
        $configuration = new \Bitmotion\Auth0\Domain\Transfer\EmAuth0Configuration();

        if ($configuration->isEnableFrontendLogin()) {
            // Register hook for showing plugin preview
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info']['auth0_loginform'][$extensionKey]
                = \Bitmotion\Auth0\Hooks\PageLayoutViewHook::class . '->getSummary';
        }

        if ($configuration->isEnableBackendLogin()) {
            // Register single log out hooks
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

        // Register Backend Module
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
            $extensionKey,
            'tools',
            'Auth0',
            'bottom',
            [
                \Bitmotion\Auth0\Controller\BackendController::class => 'list',
                \Bitmotion\Auth0\Controller\ApplicationController::class => 'list,delete',
                \Bitmotion\Auth0\Controller\RoleController::class => 'list,update',
                \Bitmotion\Auth0\Controller\PropertyController::class => 'list,new,create,edit,update,delete',
            ], [
                'access' => 'admin',
                'icon' => 'EXT:auth0/Resources/Public/Icons/Module.svg',
                'labels' => 'LLL:EXT:auth0/Resources/Private/Language/locallang_mod.xlf'
            ]
        );
    }, 'auth0'
);
