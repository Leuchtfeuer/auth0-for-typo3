<?php
declare(strict_types=1);

use Leuchtfeuer\Auth0\LoginProvider\Auth0Provider;

defined('TYPO3') || die();

// Load extension configuration
$configuration = new \Leuchtfeuer\Auth0\Domain\Transfer\EmAuth0Configuration();

if ($configuration->isEnableBackendLogin()) {
    // Register backend login provider
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['loginProviders'][Auth0Provider::LOGIN_PROVIDER] = [
        'provider' => Auth0Provider::class,
        'sorting' => 25,
        'iconIdentifier' => 'auth0LoginProvider',
        'label' => 'LLL:EXT:auth0/Resources/Private/Language/locallang.xlf:backendLogin.switch.label'
    ];
}
