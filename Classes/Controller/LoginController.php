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

namespace Leuchtfeuer\Auth0\Controller;

use Auth0\SDK\Auth0;
use Auth0\SDK\Exception\ConfigurationException;
use GuzzleHttp\Exception\GuzzleException;
use Leuchtfeuer\Auth0\Domain\Repository\ApplicationRepository;
use Leuchtfeuer\Auth0\Domain\Transfer\EmAuth0Configuration;
use Leuchtfeuer\Auth0\Factory\ApplicationFactory;
use Leuchtfeuer\Auth0\Middleware\CallbackMiddleware;
use Leuchtfeuer\Auth0\Utility\ParametersUtility;
use Leuchtfeuer\Auth0\Utility\RoutingUtility;
use Leuchtfeuer\Auth0\Utility\TokenUtility;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class LoginController extends ActionController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected string $error = '';

    protected string $errorDescription = '';

    protected ?Auth0 $auth0 = null;

    protected int $application = 0;

    protected EmAuth0Configuration $configuration;

    /**
     * @throws GuzzleException
     * @throws ConfigurationException
     */
    public function initializeAction(): void
    {
        if (!empty($this->request->getQueryParams()['error'] ?? null)) {
            $this->error = htmlspecialchars((string)$this->request->getQueryParams()['error']);
        }

        if (!empty($this->request->getQueryParams()['error_description'] ?? null)) {
            $this->errorDescription = htmlspecialchars((string)$this->request->getQueryParams()['error_description']);
        }

        $this->application = (int)($this->settings['application'] ?? $this->request->getQueryParams()['application'] ?? null);
        $this->auth0 = ApplicationFactory::build($this->application, ApplicationFactory::SESSION_PREFIX_FRONTEND);
        $this->configuration = new EmAuth0Configuration();
    }

    /**
     * @throws AspectNotFoundException
     */
    public function formAction(): ResponseInterface
    {
        if (GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('frontend.user', 'isLoggedIn')) {
            // Get Auth0 user from session storage
            $userInfo = $this->auth0->configuration()->getSessionStorage()->get('user');
        }

        $this->view->assignMultiple([
            'userInfo' => $userInfo ?? [],
            'referrer' => $this->request->getQueryParams()['referrer'] ?? $this->request->getQueryParams()['return_url'] ?? '',
            'auth0Error' => $this->error,
            'auth0ErrorDescription' => $this->errorDescription,
        ]);
        return $this->htmlResponse();
    }

    /**
     * @throws AspectNotFoundException
     * @throws ConfigurationException
     */
    public function loginAction(?string $rawAdditionalAuthorizeParameters = null): void
    {
        /** @var Context $context */
        $context = GeneralUtility::makeInstance(Context::class);
        $userInfo = $this->auth0->configuration()->getSessionStorage()->get('user');

        // Log in user to auth0 when there is neither a TYPO3 frontend user nor an Auth0 user
        if (!$context->getPropertyFromAspect('frontend.user', 'isLoggedIn') || empty($userInfo)) {
            if ($rawAdditionalAuthorizeParameters !== null && $rawAdditionalAuthorizeParameters !== '' && $rawAdditionalAuthorizeParameters !== '0') {
                $additionalAuthorizeParameters = ParametersUtility::transformUrlParameters($rawAdditionalAuthorizeParameters);
            } else {
                $additionalAuthorizeParameters = $this->settings['frontend']['login']['additionalAuthorizeParameters'] ?? [];
            }

            $this->logger->notice('Try to login user.');
            // TODO: Support $additionalAuthorizeParameters to be passed and used

            $this->redirectToUri($this->auth0->login($this->getCallback()));
        }

        $this->redirect('form');
    }

    /**
     * @throws ConfigurationException
     */
    public function logoutAction(): void
    {
        $application = GeneralUtility::makeInstance(ApplicationRepository::class)->findByUid($this->application);
        $singleLogOut = isset($this->settings['softLogout']) ? !(bool)$this->settings['softLogout'] : $application->isSingleLogOut();

        if ($singleLogOut === false) {
            $routingUtility = GeneralUtility::makeInstance(RoutingUtility::class);
            $routingUtility->addArgument('logintype', 'logout');

            if (str_contains((string) $this->settings['redirectMode'], 'logout') && (bool)$this->settings['redirectDisable'] === false) {
                $routingUtility->addArgument('referrer', $this->addLogoutRedirect());
            }
            $this->redirectToUri($routingUtility->getUri());
        }

        $this->logger->notice('Proceed with single log out.');

        if ($application->isSingleLogOut() && $this->configuration->isSoftLogout()) {
            $this->redirectToUri($this->getCallback('logout'));
        } else {
            $this->redirectToUri($this->auth0->logout($this->getCallback('logout')));
        }
    }

    protected function getCallback(string $loginType = 'login'): string
    {
        $uri = $this->request->getUri();
        $referrer = $this->request->getQueryParams()['referrer'] ?? sprintf('%s://%s%s', $uri->getScheme(), $uri->getHost(), $uri->getPath());

        //TODO: Check this functionality again. Auth0 documentation states that they remove everything anchor related to maintain OAuth2 specification
        if ($this->settings['referrerAnchor']) {
            $referrer .= '#' . $this->settings['referrerAnchor'];
        }

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
            $tokenUtility->buildToken()->toString()
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
