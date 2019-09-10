<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\LoginProvider;

/***
 *
 * This file is part of the "Auth0 for TYPO3" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2018 Florian Wessels <f.wessels@bitmotion.de>, Bitmotion GmbH
 *
 ***/

use Auth0\SDK\Store\SessionStore;
use Bitmotion\Auth0\Api\Auth0;
use Bitmotion\Auth0\Domain\Model\Dto\EmAuth0Configuration;
use Bitmotion\Auth0\Domain\Repository\ApplicationRepository;
use Bitmotion\Auth0\Exception\InvalidApplicationException;
use Bitmotion\Auth0\Utility\ApiUtility;
use Bitmotion\Auth0\Utility\ConfigurationUtility;
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
    protected $userInfo = null;

    /**
     * @var EmAuth0Configuration
     */
    protected $configuration;

    /**
     * @throws InvalidConfigurationTypeException
     */
    public function render(StandaloneView $standaloneView, PageRenderer $pageRenderer, LoginController $loginController)
    {
        $this->logger->notice('Auth0 login is used.');

        // Figure out whether TypoScript is loaded
        if (!$this->isTypoScriptLoaded()) {
            // In this case we need a default template
            $this->getDefaultView($standaloneView, $pageRenderer);

            return;
        }

        // Throw error if there is no application
        if (!$this->setAuth0()) {
            $standaloneView->assign('error', 'no_application');

            return;
        }

        $this->prepareView($standaloneView, $pageRenderer);

        // Try to get user info from session storage
        $store = new SessionStore();
        $this->userInfo = $store->get('user');

        if (($this->userInfo === null && (int)GeneralUtility::_GP('login') === 1) || (int)GeneralUtility::_GP('logout') === 1) {
            $this->handleRequest();
        }

        // Assign variables and Auth0 response to view
        $standaloneView->assignMultiple([
            'auth0Error' => GeneralUtility::_GP('error'),
            'auth0ErrorDescription' => GeneralUtility::_GP('error_description'),
            'userInfo' => $this->userInfo,
        ]);
    }

    protected function setAuth0(): bool
    {
        try {
            $this->configuration = new EmAuth0Configuration();

            $apiUtility = GeneralUtility::makeInstance(ApiUtility::class, (int)$this->configuration->getBackendConnection());
            $this->auth0 = $apiUtility->getAuth0(GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'));
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());

            return false;
        }

        return true;
    }

    protected function handleRequest(): void
    {
        // Try to get user via Auth0 API
        if ($this->userInfo === null) {
            try {
                $this->logger->notice('Try to get user via Auth0 API');
                $this->userInfo = $this->auth0->getUser();
            } catch (\Exception $exception) {
                $this->logger->critical($exception->getMessage());
                $this->auth0->deleteAllPersistentData();
            }
        }

        if (GeneralUtility::_GP('logout') == 1) {
            // Logout user from Auth0
            $this->logger->notice('Logout user.');
            $this->auth0->logout();
            $this->userInfo = null;

            if ($this->configuration->isSoftLogout() === false) {
                $this->logoutFromAuth0();
            }
        } elseif ($this->userInfo === null && GeneralUtility::_GP('login') == 1) {
            // Login user to Auth0
            $this->logger->notice('Handle backend login.');
            $this->auth0->login();
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
    protected function logoutFromAuth0()
    {
        $application = GeneralUtility::makeInstance(ApplicationRepository::class)->findByUid((int)$this->configuration->getBackendConnection());
        $redirectUri = str_replace('logout=1', '', GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'));
        $logoutUri = $this->auth0->getLogoutUri(rtrim($redirectUri, '&'), $application['id']);

        header('Location: ' . $logoutUri);
        exit;
    }
}
