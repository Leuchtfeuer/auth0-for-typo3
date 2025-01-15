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

namespace Leuchtfeuer\Auth0\LoginProvider;

use Auth0\SDK\Auth0;
use Auth0\SDK\Exception\ConfigurationException;
use GuzzleHttp\Exception\GuzzleException;
use Leuchtfeuer\Auth0\Domain\Model\Application;
use Leuchtfeuer\Auth0\Domain\Repository\ApplicationRepository;
use Leuchtfeuer\Auth0\Domain\Transfer\EmAuth0Configuration;
use Leuchtfeuer\Auth0\Factory\ApplicationFactory;
use Leuchtfeuer\Auth0\Middleware\CallbackMiddleware;
use Leuchtfeuer\Auth0\Utility\ModeUtility;
use Leuchtfeuer\Auth0\Utility\TokenUtility;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Backend\Controller\LoginController;
use TYPO3\CMS\Backend\LoginProvider\LoginProviderInterface;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewInterface;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Fluid\View\FluidViewAdapter;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\View\AbstractTemplateView as FluidStandaloneAbstractTemplateView;

class Auth0Provider implements LoginProviderInterface, LoggerAwareInterface, SingletonInterface
{
    use LoggerAwareTrait;

    public const ACTION_LOGIN = 'login';

    public const ACTION_LOGOUT = 'logout';

    public const LOGIN_PROVIDER = 1526966635;

    protected ?Application $application = null;

    protected Auth0 $auth0;

    /**
     * @var array<string, mixed>|null
     */
    protected ?array $userInfo = [];

    protected EmAuth0Configuration $configuration;

    protected ?string $action = null;

    /**
     * @var array<mixed>
     */
    protected array $frameworkConfiguration;

    protected RenderingContextInterface $renderingContext;

    public function __construct(
        protected readonly ApplicationRepository $applicationRepository,
        protected readonly PageRenderer $pageRenderer,
        protected readonly ConfigurationManager $configurationManager
    ) {}

    protected function initialize(): void
    {
        $this->configuration = new EmAuth0Configuration();
        $this->application = $this->applicationRepository->findByUid($this->configuration->getBackendConnection());
        $this->frameworkConfiguration = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK,
            'auth0'
        );
    }

    /**
     * @throws ConfigurationException
     */
    public function modifyView(ServerRequestInterface $request, ViewInterface $view): string
    {
        $this->initialize();

        $this->logger?->notice('Auth0 login is used.');
        $this->renderingContext = $this->getRenderingContext($view);

        // Figure out whether TypoScript is loaded
        if (!$this->isTypoScriptLoaded()) {
            // In this case we need a default template
            return $this->getDefaultView($view);
        }

        $templateName = $this->prepareView($view);

        // Throw error if there is no application
        if (!$this->application instanceof \Leuchtfeuer\Auth0\Domain\Model\Application) {
            $view->assign('error', 'no_application');
            return $templateName;
        }

        // Try to get user info from session storage
        $this->userInfo = $this->getUserInfo();

        $urlData = $this->getRequest()->getQueryParams()['auth0'] ?? [];
        $this->action = $urlData['action'] ?? null;

        if ((empty($this->userInfo) && $this->action === self::ACTION_LOGIN) || $this->action === self::ACTION_LOGOUT) {
            $this->handleRequest();
        }

        // Assign variables and Auth0 response to view
        $view->assignMultiple([
            'auth0Error' => $this->getRequest()->getQueryParams()['error'] ?? null,
            'auth0ErrorDescription' => $this->getRequest()->getQueryParams()['error_description'] ?? null,
            'code' => $this->getRequest()->getQueryParams()['code'] ?? null,
            'userInfo' => $this->userInfo,
        ]);

        return $templateName;
    }

    protected function setAuth0(): bool
    {
        try {
            $this->auth0 = ApplicationFactory::build($this->configuration->getBackendConnection());
        } catch (\Exception|GuzzleException $exception) {
            $this->logger?->critical($exception->getMessage());
            throw $exception;
        }

        return true;
    }

    protected function getCallback(?string $redirectUri = ''): string
    {
        $tokenUtility = new TokenUtility();
        $tokenUtility->withPayload('environment', ModeUtility::BACKEND_MODE);
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

    /**
     * @return array<mixed>
     */
    protected function getUserInfo(): array
    {
        $this->setAuth0();
        $userInfo = $this->auth0->configuration()->getSessionStorage()?->get('user') ?? [];
        if (!is_array($userInfo) || empty($userInfo)) {
            try {
                $this->logger?->notice('Try to get user via Auth0 API');
                if ($this->auth0->exchange($this->getCallback(), $this->getRequest()->getQueryParams()['code'] ?? null, $this->getRequest()->getQueryParams()['state'] ?? null)) {
                    $userInfo = $this->auth0->getUser() ?? [];
                }
            } catch (\Exception $exception) {
                $this->logger?->critical($exception->getMessage());
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
            $this->logger?->notice('Logout user.');
            $this->logoutFromAuth0();
        } elseif ($this->action === self::ACTION_LOGIN) {
            // Login user to Auth0
            $this->logger?->notice('Handle backend login.');
            header('Location: ' . $this->auth0->login($this->getCallback()));
            exit;
        }
    }

    protected function isTypoScriptLoaded(): bool
    {
        /** @extensionScannerIgnoreLine */
        return isset($this->frameworkConfiguration['settings']['stylesheet']);
    }

    protected function prepareView(ViewInterface $view): string
    {
        /** @extensionScannerIgnoreLine */
        $this->pageRenderer->addCssFile($this->frameworkConfiguration['settings']['stylesheet']);

        $templatePaths = $this->renderingContext->getTemplatePaths();
        $templatePaths->setLayoutRootPaths($this->frameworkConfiguration['view']['layoutRootPaths']);
        $templatePaths->setTemplateRootPaths($this->frameworkConfiguration['view']['templateRootPaths']);

        return $this->getTemplateName();
    }

    protected function getDefaultView(ViewInterface $view): string
    {
        $this->pageRenderer->addCssFile('EXT:auth0/Resources/Public/Styles/backend.css');

        $templatePaths = $this->renderingContext->getTemplatePaths();
        $templatePaths->setLayoutRootPaths(['EXT:auth0/Resources/Private/Layouts/']);
        $templatePaths->setTemplateRootPaths(['EXT:auth0/Resources/Private/Templates/']);

        $view->assign('error', 'no_typoscript');

        return $this->getTemplateName();
    }

    /**
     * @throws ConfigurationException
     */
    protected function logoutFromAuth0(): void
    {
        $redirectUri = GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . 'typo3/logout';
        if ($this->application?->isSingleLogOut() && $this->configuration->isSoftLogout()) {
            $this->auth0->clear();
            header('Location: ' . $redirectUri);
        } else {
            header('Location: ' . $this->auth0->logout($this->getCallback($redirectUri)));
        }
        exit();
    }

    protected function getRenderingContext(ViewInterface $view): RenderingContextInterface
    {
        if ($view instanceof FluidStandaloneAbstractTemplateView|| $view instanceof FluidViewAdapter) {
            return $view->getRenderingContext();
        }
        throw new \RuntimeException('view must be an instance of ext:fluid \TYPO3Fluid\Fluid\View\AbstractTemplateView', 1721889095);
    }

    private function getTemplateName(): string
    {
        return 'LoginProvider/Backend';
    }

    private function getRequest(): ServerRequestInterface
    {
        return $GLOBALS['TYPO3_REQUEST'];
    }

    /**
     * @deprecated
     * @extensionScannerIgnoreLine
     */
    public function render(StandaloneView $view, PageRenderer $pageRenderer, LoginController $loginController): void
    {
        throw new \RuntimeException('Should not be called in TYPO3v13');
    }
}
