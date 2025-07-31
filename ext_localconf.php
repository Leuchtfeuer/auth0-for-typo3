<?php

declare(strict_types=1);

use Leuchtfeuer\Auth0\Domain\Transfer\EmAuth0Configuration;
use Leuchtfeuer\Auth0\LoginProvider\Auth0Provider;
use Leuchtfeuer\Auth0\Service\AuthenticationService;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') || die('');

// Load libraries when TYPO3 is not in composer mode
if (!Environment::isComposerMode()) {
    require ExtensionManagementUtility::extPath('auth0') . 'Libraries/vendor/autoload.php';
}

// Load extension configuration
$configuration = new EmAuth0Configuration();

// Get proper subtypes for authentication service
$subtypes = [];
if ($configuration->isEnableBackendLogin()) {
    $subtypes[] = 'getUserBE';
    $subtypes[] = 'authUserBE';
    // Register backend login provider
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['loginProviders'][Auth0Provider::LOGIN_PROVIDER] = [
        'provider' => Auth0Provider::class,
        'sorting' => 25,
        'iconIdentifier' => 'auth0LoginProvider',
        'label' => 'LLL:EXT:auth0/Resources/Private/Language/locallang.xlf:backendLogin.switch.label'
    ];
}

if ($subtypes !== []) {
    // Get priority for Auth0 Authentication Service
    $highestPriority = 0;

    foreach ($GLOBALS['T3_SERVICES']['auth'] ?? [] as $service) {
        if ($service['priority'] > $highestPriority) {
            $highestPriority = $service['priority'];
        }
    }

    $overrulingPriority = $highestPriority + 10;

    // Register login provider
    ExtensionManagementUtility::addService(
        'auth0',
        'auth',
        AuthenticationService::class,
        [
            'title' => 'Auth0 authentication',
            'description' => 'Authentication with Auth0.',
            'subtype' => implode(',', $subtypes),
            'available' => true,
            'priority' => $overrulingPriority,
            'quality' => $overrulingPriority,
            'os' => '',
            'exec' => '',
            'className' => AuthenticationService::class,
        ]
    );
}

ExtensionManagementUtility::addTypoScript(
    'my_extension',
    'constants',
    "@import 'EXT:auth0/Configuration/TypoScript/constants.typoscript'",
);

ExtensionManagementUtility::addTypoScript(
    'my_extension',
    'setup',
    "@import 'EXT:auth0/Configuration/TypoScript/setup.typoscript'",
);
