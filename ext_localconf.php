<?php
defined('TYPO3_MODE') || die();

if (!defined('TYPO3_COMPOSER_MODE') || !TYPO3_COMPOSER_MODE) {
    require \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('auth0') . 'Libraries/vendor/autoload.php';
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Bitmotion.' . $_EXTKEY,
    'LoginForm',
    [
        'Login' => 'form, login, logout',
    ],
    // non-cacheable actions
    [
        'Login' => 'form, login, logout',
    ]
);

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info']['auth0_loginform']['auth0'] =
    \Bitmotion\Auth0\Hooks\PageLayoutViewHook::class . '->getSummary';



$configuration = new \Bitmotion\Auth0\Domain\Model\Dto\EmAuth0Configuration();
if ($configuration->getEnableBackendLogin() === true) {
    $subtypes = 'authUserFE,getUserFE,getUserBE,authUserBE';
    if (TYPO3_MODE === 'BE') {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['loginProviders'][1526966635] = [
            'provider' => \Bitmotion\Auth0\LoginProvider\Auth0Provider::class,
            'sorting' => 25,
            'icon-class' => 'fa-sign-in',
            'label' => 'LLL:EXT:auth0/Resources/Private/Language/locallang.xlf:backendLogin.switch.label'
        ];
    }
} else {
    $subtypes = 'authUserFE,getUserFE';
}

$highestPriority = 0;

if (is_array($GLOBALS['T3_SERVICES']['auth'])) {
    foreach ($GLOBALS['T3_SERVICES']['auth'] as $service) {
        if ($service['priority'] > $highestPriority) {
            $highestPriority = $service['priority'];
        }
    }
}

$overrulingPriority = $highestPriority + 10;

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService(
    'auth0',
    'auth',
    \Bitmotion\Auth0\Service\AuthenticationService::class,
    [
        'title' => 'Auth0 Authentication',
        'description' => 'Authenticates with Auth0 credentials.',
        'subtype' => $subtypes,
        'available' => true,
        'priority' => $overrulingPriority,
        'quality' => $overrulingPriority,
        'os' => '',
        'exec' => '',
        'className' => \Bitmotion\Auth0\Service\AuthenticationService::class
    ]
);

$signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
$signalSlotDispatcher->connect(
    \TYPO3\CMS\Extensionmanager\Utility\InstallUtility::class,
    'afterExtensionInstall',
    \Bitmotion\Auth0\Slots\ConfigurationSlot::class,
    'addCacheHashExcludedParameters'
);

$GLOBALS['TYPO3_CONF_VARS']['LOG']['Bitmotion']['Auth0'] = [
    'writerConfiguration' => [
        \TYPO3\CMS\Core\Log\LogLevel::DEBUG => [
            \TYPO3\CMS\Core\Log\Writer\NullWriter::class => []
        ]
    ]
];

$GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['setup']['FE_fetchUserIfNoSession'] = true;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['logoff_post_processing'][] = \Bitmotion\Auth0\Hooks\SingleSignOutHook::class . '->perform';