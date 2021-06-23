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

namespace Bitmotion\Auth0\Controller;

use Auth0\SDK\Exception\CoreException;
use Bitmotion\Auth0\Api\Auth0;
use Bitmotion\Auth0\Domain\Repository\ApplicationRepository;
use Bitmotion\Auth0\Domain\Transfer\EmAuth0Configuration;
use Bitmotion\Auth0\Exception\InvalidApplicationException;
use Bitmotion\Auth0\Factory\SessionFactory;
use Bitmotion\Auth0\Middleware\CallbackMiddleware;
use Bitmotion\Auth0\Utility\ApiUtility;
use Bitmotion\Auth0\Utility\ConfigurationUtility;
use Bitmotion\Auth0\Utility\ParametersUtility;
use Bitmotion\Auth0\Utility\RoutingUtility;
use Bitmotion\Auth0\Utility\TokenUtility;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;

class LoginController extends ActionController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected $error = '';

    protected $errorDescription = '';

    protected $auth0;

    protected $application = 0;

    /**
     * @var EmAuth0Configuration
     */
    protected $extensionConfiguration;

    /**
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
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
        $this->extensionConfiguration = new EmAuth0Configuration();
    }

    /**
     * @throws AspectNotFoundException
     * @throws CoreException
     * @throws InvalidApplicationException
     * @throws StopActionException
     * @throws \ReflectionException
     */
    public function formAction(): void
    {
        if (GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('frontend.user', 'isLoggedIn')) {
            // Get Auth0 user from session storage
            $userInfo = (new SessionFactory())->getSessionStoreForApplication($this->application, SessionFactory::SESSION_PREFIX_FRONTEND)->getUserInfo();
        }

        $this->view->assignMultiple([
            'userInfo' => $userInfo ?? [],
            'referrer' => GeneralUtility::_GET('referrer') ?? GeneralUtility::_GET('return_url') ?? '',
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
     */
    public function loginAction(?string $rawAdditionalAuthorizeParameters = null): void
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $userInfo = (new SessionFactory())->getSessionStoreForApplication($this->application, SessionFactory::SESSION_PREFIX_FRONTEND)->getUserInfo();

        // Log in user to auth0 when there is neither a TYPO3 frontend user nor an Auth0 user
        if (!$context->getPropertyFromAspect('frontend.user', 'isLoggedIn') || empty($userInfo)) {
            if (!empty($rawAdditionalAuthorizeParameters)) {
                $additionalAuthorizeParameters = ParametersUtility::transformUrlParameters($rawAdditionalAuthorizeParameters);
            } else {
                $additionalAuthorizeParameters = $this->settings['frontend']['login']['additionalAuthorizeParameters'] ?? [];
            }

            $this->logger->notice('Try to login user.');
            $this->getAuth0()->login(null, null, $additionalAuthorizeParameters);
        }

        $this->redirect('form');
    }

    /**
     * @throws CoreException
     * @throws InvalidApplicationException
     * @throws StopActionException
     */
    public function logoutAction(): void
    {
        $application = GeneralUtility::makeInstance(ApplicationRepository::class)->findByUid($this->application);
        $singleLogOut = isset($this->settings['softLogout']) ? !(bool)$this->settings['softLogout'] : $application->isSingleLogOut();

        if ($singleLogOut === false) {
            $routingUtility = GeneralUtility::makeInstance(RoutingUtility::class);
            $routingUtility->addArgument('logintype', 'logout');

            if (strpos($this->settings['redirectMode'], 'logout') !== false && (bool)$this->settings['redirectDisable'] === false) {
                $routingUtility->addArgument('referrer', $this->addLogoutRedirect());
            }

            $returnUrl = $routingUtility->getUri();

            if ($singleLogOut === false) {
                $this->redirectToUri($returnUrl);
            }
        }

        $this->logger->notice('Proceed with single log out.');
        $auth0 = $this->getAuth0();
        $auth0->logout();

        $this->redirectToUri($auth0->getLogoutUri($this->getCallback('logout'), $application->getClientId()));
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

        return GeneralUtility::makeInstance(ApiUtility::class, $this->application)->getAuth0($this->getCallback());
    }

    protected function getCallback(string $loginType = 'login'): string
    {
        $uri = $GLOBALS['TYPO3_REQUEST']->getUri();
        $referrer = $GLOBALS['TYPO3_REQUEST']->getQueryParams()['referrer'] ?? sprintf('%s://%s%s', $uri->getScheme(), $uri->getHost(), $uri->getPath());

        $tokenUtility = GeneralUtility::makeInstance(TokenUtility::class);
        $tokenUtility->withPayload('application', $this->application);
        $tokenUtility->withPayload('referrer', $referrer);
        $tokenUtility->withPayload('redirectMode', $this->settings['redirectMode']);
        $tokenUtility->withPayload('redirectFirstMethod', $this->settings['redirectFirstMethod']);
        $tokenUtility->withPayload('redirectPageLogin', $this->settings['redirectPageLogin']);
        $tokenUtility->withPayload('redirectPageLoginError', $this->settings['redirectPageLoginError']);
        $tokenUtility->withPayload('redirectPageLogout', $this->settings['redirectPageLogout']);
        $tokenUtility->withPayload('redirectDisable', $this->settings['redirectDisable']);

        return sprintf(
            '%s%s?logintype=%s&%s=%s',
            $tokenUtility->getIssuer(),
            CallbackMiddleware::PATH,
            $loginType,
            CallbackMiddleware::TOKEN_PARAMETER,
            $tokenUtility->buildToken()
        );
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
