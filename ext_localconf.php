<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function ($extensionKey) {
        // Load libraries when TYPO3 is not in composer mode
        if (!defined('TYPO3_COMPOSER_MODE') || !TYPO3_COMPOSER_MODE) {
            require \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($extensionKey) . 'Libraries/vendor/autoload.php';
        }

        // Define some variables depending on TYPO3 Version
        // TODO: Remove this when dropping TYPO3 9 LTS support.
        if (version_compare(TYPO3_version, '10.0.0', '>=')) {
            $extensionName = 'Auth0';
            $controllerName = \Bitmotion\Auth0\Controller\LoginController::class;
        } else {
            $extensionName = 'Bitmotion.Auth0';
            $controllerName = 'Login';
        }

        // Configure Auth0 plugin
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            $extensionName,
            'LoginForm',
            [$controllerName => 'form, login, logout'],
            [$controllerName => 'form, login, logout']
        );

        // Load extension configuration
        $configuration = new \Bitmotion\Auth0\Domain\Transfer\EmAuth0Configuration();

        // Get proper subtypes for authentication service
        $subtypes = 'authUserFE,getUserFE';
        if ($configuration->getEnableBackendLogin() === true) {
            $subtypes .= ',getUserBE,authUserBE';
        }

        // Get priority for Auth0 Authentication Service
        $highestPriority = 0;
        if (is_array($GLOBALS['T3_SERVICES']['auth'])) {
            foreach ($GLOBALS['T3_SERVICES']['auth'] as $service) {
                if ($service['priority'] > $highestPriority) {
                    $highestPriority = $service['priority'];
                }
            }
        }
        $overrulingPriority = $highestPriority + 10;

        // Register login provider
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService(
            $extensionKey,
            'auth',
            \Bitmotion\Auth0\Service\AuthenticationService::class,
            [
                'title' => 'Auth0 authentication',
                'description' => 'Authentication with Auth0.',
                'subtype' => $subtypes,
                'available' => true,
                'priority' => $overrulingPriority,
                'quality' => $overrulingPriority,
                'os' => '',
                'exec' => '',
                'className' => \Bitmotion\Auth0\Service\AuthenticationService::class
            ]
        );

        // Register logger
        $GLOBALS['TYPO3_CONF_VARS']['LOG']['Bitmotion']['Auth0'] = [
            'writerConfiguration' => [
                \TYPO3\CMS\Core\Log\LogLevel::DEBUG => [
                    \TYPO3\CMS\Core\Log\Writer\NullWriter::class => []
                ],
            ],
        ];
    }, 'auth0'
);
