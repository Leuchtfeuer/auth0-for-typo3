<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function ($extensionKey) {
        // Load libraries when TYPO3 is not in composer mode
        // TODO: Use environment class when dropping TYPO3 v9 support.
        if (!defined('TYPO3_COMPOSER_MODE') || !TYPO3_COMPOSER_MODE) {
            require \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($extensionKey) . 'Libraries/vendor/autoload.php';
        }

        // Load extension configuration
        $configuration = new \Bitmotion\Auth0\Domain\Transfer\EmAuth0Configuration();

        // Get proper subtypes for authentication service
        $subtypes = [];
        if ($configuration->isEnableFrontendLogin()) {
            $subtypes[] = 'authUserFE';
            $subtypes[] = 'getUserFE';

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
        }

        if ($configuration->isEnableBackendLogin()) {
            $subtypes[] = 'getUserBE';
            $subtypes[] = 'authUserBE';
        }

        if (!empty($subtypes)) {
            // Get priority for Auth0 Authentication Service
            $highestPriority = 0;

            foreach ($GLOBALS['T3_SERVICES']['auth'] ?? [] as $service) {
                if ($service['priority'] > $highestPriority) {
                    $highestPriority = $service['priority'];
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
                    'subtype' => implode(',', $subtypes),
                    'available' => true,
                    'priority' => $overrulingPriority,
                    'quality' => $overrulingPriority,
                    'os' => '',
                    'exec' => '',
                    'className' => \Bitmotion\Auth0\Service\AuthenticationService::class
                ]
            );
        }
    }, 'auth0'
);
