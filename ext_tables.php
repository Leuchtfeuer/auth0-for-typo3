<?php
declare(strict_types=1);

use Leuchtfeuer\Auth0\Controller\ApplicationController;
use Leuchtfeuer\Auth0\Controller\BackendController;
use Leuchtfeuer\Auth0\Controller\PropertyController;
use Leuchtfeuer\Auth0\Controller\RoleController;
use Leuchtfeuer\Auth0\LoginProvider\Auth0Provider;

defined('TYPO3') or die();

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
$iconRegistry->registerIcon(
    'moduleAuth0',
    \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
    [
        'source' => 'EXT:auth0/Resources/Public/Icons/Module.svg',
    ]
);
$iconRegistry->registerIcon(
    'auth0LoginProvider',
    \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
    [
        'source' => 'EXT:auth0/Resources/Public/Icons/sign-in.svg',
    ]
);

// Load extension configuration
$configuration = new \Leuchtfeuer\Auth0\Domain\Transfer\EmAuth0Configuration();

if ($configuration->isEnableFrontendLogin()) {
    // Register hook for showing plugin preview
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info']['auth0_loginform']['auth0']
        = \Leuchtfeuer\Auth0\Hooks\PageLayoutViewHook::class . '->getSummary';
}

if ($configuration->isEnableBackendLogin()) {
    // Register backend login provider
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['loginProviders'][Auth0Provider::LOGIN_PROVIDER] = [
        'provider' => Auth0Provider::class,
        'sorting' => 25,
        'iconIdentifier' => 'auth0LoginProvider',
        'label' => 'LLL:EXT:auth0/Resources/Private/Language/locallang.xlf:backendLogin.switch.label'
    ];

    // Register single log out hooks
    // TODO: Support following hooks for frontend request as well and move to ext_localconf.php file
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['logoff_pre_processing']['auth0']
        = \Leuchtfeuer\Auth0\Hooks\SingleSignOutHook::class . '->isResponsible';
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['logoff_post_processing']['auth0']
        = \Leuchtfeuer\Auth0\Hooks\SingleSignOutHook::class . '->performLogout';
}

// Register Backend Module
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
    'auth0',
    'tools',
    'Auth0',
    'bottom',
    [
        BackendController::class => 'list',
        ApplicationController::class => 'list,delete',
        RoleController::class => 'list,update',
        PropertyController::class => 'list,new,create,edit,update,delete',
    ], [
        'access' => 'admin',
        'icon' => 'EXT:auth0/Resources/Public/Icons/Module.svg',
        'labels' => 'LLL:EXT:auth0/Resources/Private/Language/locallang_mod.xlf'
    ]
);