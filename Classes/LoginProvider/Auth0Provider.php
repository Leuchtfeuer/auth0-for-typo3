<?php

declare(strict_types=1);

/*
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

namespace Bitmotion\Auth0\LoginProvider;

use Bitmotion\Auth0\Api\Auth0;
use Bitmotion\Auth0\Domain\Repository\ApplicationRepository;
use Bitmotion\Auth0\Domain\Transfer\EmAuth0Configuration;
use Bitmotion\Auth0\Exception\InvalidApplicationException;
use Bitmotion\Auth0\Factory\SessionFactory;
use Bitmotion\Auth0\Middleware\CallbackMiddleware;
use Bitmotion\Auth0\Utility\ApiUtility;
use Bitmotion\Auth0\Utility\ConfigurationUtility;
use Bitmotion\Auth0\Utility\TokenUtility;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Backend\Controller\LoginController;
use TYPO3\CMS\Backend\LoginProvider\LoginProviderInterface;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException;
use TYPO3\CMS\Fluid\View\StandaloneView;

class Auth0Provider implements LoginProviderInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const ACTION_LOGIN = 'login';

    public const ACTION_LOGOUT = 'logout';

    public const LOGIN_PROVIDER = 1526966635;

    /**
     * @var Auth0
     */
    protected $auth0;

    /**
     * @var array
     */
    protected $userInfo = [];

    /**
     * @var EmAuth0Configuration
     */
    protected $configuration;

    /**
     * @var ?string
     */
    protected $action;

    /**
     * @throws InvalidConfigurationTypeException
     */
    public function render(StandaloneView $standaloneView, PageRenderer $pageRenderer, LoginController $loginController): void
    {
        $this->logger->notice('Auth0 login is used.');

        // Figure out whether TypoScript is loaded
        if (!$this->isTypoScriptLoaded()) {
            // In this case we need a default template
            $this->getDefaultView($standaloneView, $pageRenderer);

            return;
        }

        $this->prepareView($standaloneView, $pageRenderer);

        // Throw error if there is no application
        if (!$this->setAuth0()) {
            $standaloneView->assign('error', 'no_application');

            return;
        }

        // Try to get user info from session storage
        $this->userInfo = $this->getUserInfo();
        $urlData = GeneralUtility::_GET('auth0') ?? [];
        $this->action = $urlData['action'] ?? null;

        if ((empty($this->userInfo) && $this->action === self::ACTION_LOGIN) || $this->action === self::ACTION_LOGOUT) {
            $this->handleRequest();
        }

        // Assign variables and Auth0 response to view
        $standaloneView->assignMultiple([
            'auth0Error' => GeneralUtility::_GET('error'),
            'auth0ErrorDescription' => GeneralUtility::_GET('error_description'),
            'code' => GeneralUtility::_GET('code'),
            'userInfo' => $this->userInfo,
        ]);
    }

    protected function setAuth0(): bool
    {
        try {
            $this->configuration = new EmAuth0Configuration();
            $apiUtility = GeneralUtility::makeInstance(ApiUtility::class, $this->configuration->getBackendConnection());
            $this->auth0 = $apiUtility->getAuth0($this->getCallbackUri());
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());

            return false;
        }

        return true;
    }

    protected function getCallbackUri()
    {
        $callback = GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL');

        if ($this->configuration->isGenericCallback()) {
            $tokenUtility = new TokenUtility();
            $tokenUtility->withPayload('application', $this->configuration->getBackendConnection());

            $callback = sprintf(
                '%s%s?%s=%s',
                $tokenUtility->getIssuer(),
                CallbackMiddleware::PATH,
                CallbackMiddleware::TOKEN_PARAMETER,
                $tokenUtility->buildToken()
            );
        }

        return $callback;
    }

    protected function getUserInfo()
    {
        $userInfo = (new SessionFactory())->getSessionStoreForApplication($this->configuration->getBackendConnection())->getUserInfo();

        if (empty($userInfo)) {
            try {
                $this->logger->notice('Try to get user via Auth0 API');
                $userInfo = $this->auth0->getUser();
            } catch (\Exception $exception) {
                $this->logger->critical($exception->getMessage());
                $this->auth0->deleteAllPersistentData();
            }
        }

        return $userInfo;
    }

    protected function handleRequest(): void
    {
        if ($this->action === self::ACTION_LOGOUT) {
            // Logout user from Auth0
            $this->logger->notice('Logout user.');
            $this->logoutFromAuth0();
        } elseif ($this->action === self::ACTION_LOGIN) {
            // Login user to Auth0
            $this->logger->notice('Handle backend login.');
            $this->auth0->login(null, null, $this->configuration->getAdditionalAuthorizeParameters());
        }
    }

    protected function isTypoScriptLoaded(): bool
    {
        try {
            ConfigurationUtility::getSetting('propertyMapping');
        } catch (\Exception $exception) {
            $this->logger->notice($exception->getMessage());

            return false;
        }

        return true;
    }

    /**
     * @throws InvalidConfigurationTypeException
     */
    protected function prepareView(StandaloneView &$standaloneView, PageRenderer &$pageRenderer): void
    {
        $backendViewSettings = ConfigurationUtility::getSetting('backend', 'view');
        $standaloneView->setLayoutRootPaths([$backendViewSettings['layoutPath']]);
        $standaloneView->setTemplatePathAndFilename(
            GeneralUtility::getFileAbsFileName($backendViewSettings['templateFile'])
        );
        $pageRenderer->addCssFile($backendViewSettings['stylesheet']);
    }

    protected function getDefaultView(StandaloneView &$standaloneView, PageRenderer &$pageRenderer): void
    {
        $standaloneView->setLayoutRootPaths(['EXT:auth0/Resources/Private/Layouts/']);
        $standaloneView->setTemplatePathAndFilename(
            GeneralUtility::getFileAbsFileName('EXT:auth0/Resources/Private/Templates/Backend.html')
        );
        $standaloneView->assign('error', 'no_typoscript');
        $pageRenderer->addCssFile('EXT:auth0/Resources/Public/Styles/backend.css');
    }

    /**
     * @throws InvalidApplicationException
     */
    protected function logoutFromAuth0(): void
    {
        $this->auth0->logout();

        $applicationRepository = GeneralUtility::makeInstance(ApplicationRepository::class);
        $application = $applicationRepository->findByUid($this->configuration->getBackendConnection(), true);
        $redirectUri = str_replace('auth0[action]=logout', '', GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'));
        $logoutUri = $this->auth0->getLogoutUri(rtrim($redirectUri, '&'), $application->getClientId());

        header('Location: ' . $logoutUri);
        exit;
    }
}
