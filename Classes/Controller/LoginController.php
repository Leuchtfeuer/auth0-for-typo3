<?php
declare(strict_types = 1);
namespace Bitmotion\Auth0\Controller;

/***
 *
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2018 Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 *
 ***/

use Auth0\SDK\Exception\ApiException;
use Auth0\SDK\Exception\CoreException;
use Bitmotion\Auth0\Api\Auth0;
use Bitmotion\Auth0\Domain\Repository\ApplicationRepository;
use Bitmotion\Auth0\Exception\InvalidApplicationException;
use Bitmotion\Auth0\Service\RedirectService;
use Bitmotion\Auth0\Utility\ApiUtility;
use Bitmotion\Auth0\Utility\ConfigurationUtility;
use Bitmotion\Auth0\Utility\ParametersUtility;
use Bitmotion\Auth0\Utility\RoutingUtility;
use Bitmotion\Auth0\Utility\UserUtility;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;
use TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException;

class LoginController extends ActionController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected $error = '';

    protected $errorDescription = '';

    protected $auth0;

    protected $application = 0;

    /**
     * @throws InvalidConfigurationTypeException
     */
    public function initializeAction(): void
    {
        if (!ConfigurationUtility::isLoaded()) {
            throw new InvalidConfigurationTypeException('No TypoScript found.', 1547449321);
        }

        if (!empty(GeneralUtility::_GET('error'))) {
            $this->error = htmlspecialchars((string)GeneralUtility::_GET('error'));
        }

        if (!empty(GeneralUtility::_GET('error_description'))) {
            $this->errorDescription = htmlspecialchars((string)GeneralUtility::_GET('error_description'));
        }

        $this->application = (int)($this->settings['application'] ?? GeneralUtility::_GET('application'));
    }

    /**
     * @throws AspectNotFoundException
     * @throws CoreException
     * @throws InvalidApplicationException
     * @throws StopActionException
     * @throws UnsupportedRequestTypeException
     * @throws ApiException
     */
    public function formAction(): void
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $redirectService = GeneralUtility::makeInstance(RedirectService::class, $this->settings);

        if ($context->getPropertyFromAspect('frontend.user', 'isLoggedIn')) {
            // Get Auth0 user from session storage
            try {
                $auth0User = $this->getAuth0()->getUser();
            } catch (\Exception $exception) {
                $this->logger->warning(sprintf('%s: %s', $exception->getCode(), $exception->getMessage()));
                $auth0User = null;
            }

            // Redirect when user just logged in (and update him)
            if (GeneralUtility::_GET('logintype') === 'login' && $auth0User !== null) {
                if (!empty(GeneralUtility::_GP('referrer'))) {
                    $this->logger->notice('Handle referrer redirect prior to updating user.');
                    $redirectService->forceRedirectByReferrer(['logintype' => 'login']);
                }

                GeneralUtility::makeInstance(UserUtility::class)->updateUser($this->getAuth0(), (int)$this->settings['application']);
                $redirectService->handleRedirect(['groupLogin', 'userLogin', 'login', 'getpost', 'referrer']);
            }
        } elseif (GeneralUtility::_GET('logintype') === 'logout' && !empty(GeneralUtility::_GET('referrer'))) {
            $this->redirectToUri(GeneralUtility::_GET('referrer'));
        }

        // Force redirect due to Auth0 sign up or log in errors
        if (!empty(GeneralUtility::_GET('referrer')) && $this->error === Auth0::ERROR_UNAUTHORIZED) {
            $this->logger->notice('Handle referrer redirect because of Auth0 errors.');
            $redirectService->forceRedirectByReferrer([
                'error' => $this->error,
                'error_description' => $this->errorDescription,
            ]);
        }

        $this->view->assignMultiple([
            'userInfo' => $auth0User ?? [],
            'auth0Error' => $this->error,
            'auth0ErrorDescription' => $this->errorDescription,
        ]);
    }

    /**
     * @param string $rawAdditionalAuthorizeParameters
     *
     * @throws AspectNotFoundException
     * @throws CoreException
     * @throws InvalidApplicationException
     * @throws StopActionException
     * @throws UnsupportedRequestTypeException
     */
    public function loginAction(?string $rawAdditionalAuthorizeParameters = null): void
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $typo3User = $context->getPropertyFromAspect('frontend.user', 'id');
        $auth0 = $this->getAuth0();

        // Log in user to auth0 when there is neither a TYPO3 frontend user nor an Auth0 user
        if (!$context->getPropertyFromAspect('frontend.user', 'isLoggedIn') || empty($userInfo)) {

        if ($auth0User === null || $typo3User === 0) {
            $additionalAuthorizeParameters = !empty($rawAdditionalAuthorizeParameters)
                ? ParametersUtility::transformUrlParameters($rawAdditionalAuthorizeParameters)
                : $this->settings['frontend']['login']['additionalAuthorizeParameters'] ?? [];

            $this->logger->notice('Try to login user.');
            $auth0->login(null, null, $additionalAuthorizeParameters);
        }

        $this->redirect('form');
    }

    /**
     * @throws CoreException
     * @throws InvalidApplicationException
     * @throws StopActionException
     * @throws UnsupportedRequestTypeException
     */
    public function logoutAction(): void
    {
        $application = GeneralUtility::makeInstance(ApplicationRepository::class)->findByUid((int)$this->settings['application'], true);
        $logoutSettings = $this->settings['frontend']['logout'] ?? [];
        $singleLogOut = isset($this->settings['softLogout']) ? !(bool)$this->settings['softLogout'] : $application->isSingleLogOut();

        $routingUtility = GeneralUtility::makeInstance(RoutingUtility::class);
        $routingUtility->setCallback((int)$logoutSettings['targetPageUid'], (int)$logoutSettings['targetPageType']);
        $routingUtility->addArgument('logintype', 'logout');

        if (strpos($this->settings['redirectMode'], 'logout') !== false && (bool)$this->settings['redirectDisable'] === false) {
            $routingUtility->addArgument('referrer', $this->addLogoutRedirect());
        }

        if ($singleLogOut === false) {
            $this->redirectToUri($routingUtility->getUri());
        }

        $this->logger->notice('Proceed with single log out.');
        $auth0 = $this->getAuth0();
        $auth0->logout();
        $logoutUri = $auth0->getLogoutUri($routingUtility->getUri(), $application->getClientId());

        $this->redirectToUri($logoutUri);
    }

    /**
     * @throws CoreException
     * @throws InvalidApplicationException
     */
    protected function getAuth0(): Auth0
    {
        if ($this->auth0 instanceof Auth0) {
            return $this->auth0;
        }

        $callbackSettings = $this->settings['frontend']['callback'] ?? [];
        $apiUtility = GeneralUtility::makeInstance(ApiUtility::class, (int)($this->settings['application'] ?? GeneralUtility::_GET('application')));
        $routingUtility = GeneralUtility::makeInstance(RoutingUtility::class);
        $referrer = $routingUtility->getUri();
        $redirectUri = $routingUtility
            ->addArgument('logintype', 'login')
            ->addArgument('application', (int)$this->settings['application'])
            ->addArgument('referrer', $referrer)
            ->setCallback((int)$callbackSettings['targetPageUid'], (int)$callbackSettings['targetPageType'])
            ->getUri();
        $this->auth0 = $apiUtility->getAuth0($redirectUri);

        return $this->auth0;
    }

    protected function addLogoutRedirect(): string
    {
        $routingUtility = GeneralUtility::makeInstance(RoutingUtility::class);

        if (!empty($this->settings['redirectPageLogout'])) {
            $routingUtility->setTargetPage((int)$this->settings['redirectPageLogout']);
        }

        return $routingUtility->getUri();
    }
}
