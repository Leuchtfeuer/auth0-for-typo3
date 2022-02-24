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

use Auth0\SDK\Auth0;
use Auth0\SDK\Exception\ConfigurationException;
use Bitmotion\Auth0\Domain\Model\Application;
use Bitmotion\Auth0\Domain\Repository\ApplicationRepository;
use Bitmotion\Auth0\Domain\Transfer\EmAuth0Configuration;
use Bitmotion\Auth0\Factory\ApplicationFactory;
use Bitmotion\Auth0\Middleware\CallbackMiddleware;
use Bitmotion\Auth0\Utility\TokenUtility;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Backend\Controller\LoginController;
use TYPO3\CMS\Backend\LoginProvider\LoginProviderInterface;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException;
use TYPO3\CMS\Fluid\View\StandaloneView;

class Auth0Provider implements LoginProviderInterface, LoggerAwareInterface, SingletonInterface
{
    use LoggerAwareTrait;

    public const ACTION_LOGIN = 'login';

    public const ACTION_LOGOUT = 'logout';

    public const LOGIN_PROVIDER = 1526966635;

    protected ?Application $application = null;

    protected Auth0 $auth0;

    protected ?array $userInfo = [];

    protected EmAuth0Configuration $configuration;

    protected ?string $action;

    protected array $frameworkConfiguration;

    /**
     * @throws InvalidConfigurationTypeException
     */
    public function __construct(ConfigurationManager $configurationManager)
    {
        $this->configuration = new EmAuth0Configuration();
        $this->application = GeneralUtility::makeInstance(ApplicationRepository::class)->findByUid($this->configuration->getBackendConnection());
        $this->frameworkConfiguration = $configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK, 'auth0');
    }

    public function render(StandaloneView $view, PageRenderer $pageRenderer, LoginController $loginController): void
    {
        $this->logger->notice('Auth0 login is used.');

        // Figure out whether TypoScript is loaded
        if (!$this->isTypoScriptLoaded()) {
            // In this case we need a default template
            $this->getDefaultView($view, $pageRenderer);
            return;
        }

        $this->prepareView($view, $pageRenderer);

        // Throw error if there is no application
        if (!$this->application) {
            $view->assign('error', 'no_application');
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
        $view->assignMultiple([
            'auth0Error' => GeneralUtility::_GET('error'),
            'auth0ErrorDescription' => GeneralUtility::_GET('error_description'),
            'code' => GeneralUtility::_GET('code'),
            'userInfo' => $this->userInfo,
        ]);
    }

    protected function setAuth0(): bool
    {
        try {
            $this->auth0 = ApplicationFactory::build($this->configuration->getBackendConnection());
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());
            return false;
        } catch (GuzzleException $exception) {
            $this->logger->critical($exception->getMessage());
            return false;
        }

        return true;
    }

    protected function getCallback(?string $redirectUri = ''): string
    {
        $tokenUtility = new TokenUtility();
        $tokenUtility->withPayload('application', $this->configuration->getBackendConnection());

        if ($redirectUri !== '') {
            $tokenUtility->withPayload('redirectUri', $redirectUri);
        }

        return sprintf(
            '%s%s?%s=%s',
            $tokenUtility->getIssuer(),
            CallbackMiddleware::PATH,
            CallbackMiddleware::TOKEN_PARAMETER,
            $tokenUtility->buildToken()->toString()
        );
    }

    protected function getUserInfo()
    {
        $this->setAuth0();
        $userInfo = $this->auth0->configuration()->getSessionStorage()->get('user');
        if (empty($userInfo)) {
            try {
                $this->logger->notice('Try to get user via Auth0 API');
                if ($this->auth0->exchange($this->getCallback(), GeneralUtility::_GET('code'), GeneralUtility::_GET('state'))) {
                    $userInfo = $this->auth0->getUser();
                }
            } catch (\Exception $exception) {
                $this->logger->critical($exception->getMessage());
                $this->auth0->clear();
            }
        }

        return $userInfo;
    }

    /**
     * @throws ConfigurationException
     */
    protected function handleRequest(): void
    {
        if ($this->action === self::ACTION_LOGOUT) {
            // Logout user from Auth0
            $this->logger->notice('Logout user.');
            $this->logoutFromAuth0();
        } elseif ($this->action === self::ACTION_LOGIN) {
            // Login user to Auth0
            $this->logger->notice('Handle backend login.');
            header('Location: ' . $this->auth0->login($this->getCallback()));
        }
    }

    protected function isTypoScriptLoaded(): bool
    {
        return isset($this->frameworkConfiguration['settings']['stylesheet']);
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

    protected function prepareView(StandaloneView &$standaloneView, PageRenderer &$pageRenderer): void
    {
        $templateName = version_compare(GeneralUtility::makeInstance(Typo3Version::class)->getVersion(), '11.0', '>=') ? 'BackendV11' : 'Backend';
        $standaloneView->setTemplate($templateName);
        $standaloneView->setLayoutRootPaths($this->frameworkConfiguration['view']['layoutRootPaths']);
        $standaloneView->setTemplateRootPaths($this->frameworkConfiguration['view']['templateRootPaths']);

        $pageRenderer->addCssFile($this->frameworkConfiguration['settings']['stylesheet']);
    }

    /**
     * @throws ConfigurationException
     */
    protected function logoutFromAuth0(): void
    {
        $redirectUri = GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . 'typo3/logout';
        header('Location: ' . $this->auth0->logout($this->getCallback($redirectUri)));
        exit();
    }
}
