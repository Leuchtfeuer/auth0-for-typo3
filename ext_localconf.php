<?php

defined('TYPO3') || die('');

call_user_func(
    function ($extensionKey) {
        // Load libraries when TYPO3 is not in composer mode
        if (\TYPO3\CMS\Core\Core\Environment::isComposerMode() !== true) {
            require \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($extensionKey) . 'Libraries/vendor/autoload.php';
        }

        // Load extension configuration
        $configuration = new \Leuchtfeuer\Auth0\Domain\Transfer\EmAuth0Configuration();

        // Get proper subtypes for authentication service
        $subtypes = [];
        if ($configuration->isEnableFrontendLogin()) {
            $subtypes[] = 'authUserFE';
            $subtypes[] = 'getUserFE';

            // Configure Auth0 plugin
            \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
                $extensionKey,
                'LoginForm',
                [\Leuchtfeuer\Auth0\Controller\LoginController::class => 'form, login, logout'],
                [\Leuchtfeuer\Auth0\Controller\LoginController::class => 'form, login, logout']
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
                \Leuchtfeuer\Auth0\Service\AuthenticationService::class,
                [
                    'title' => 'Auth0 authentication',
                    'description' => 'Authentication with Auth0.',
                    'subtype' => implode(',', $subtypes),
                    'available' => true,
                    'priority' => $overrulingPriority,
                    'quality' => $overrulingPriority,
                    'os' => '',
                    'exec' => '',
                    'className' => \Leuchtfeuer\Auth0\Service\AuthenticationService::class
                ]
            );
        }
    }, 'auth0'
);
