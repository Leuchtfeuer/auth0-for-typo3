<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Controller;

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

use Auth0\SDK\Exception\CoreException;
use Auth0\SDK\Store\SessionStore;
use Bitmotion\Auth0\Api\AuthenticationApi;
use Bitmotion\Auth0\Domain\Model\Application;
use Bitmotion\Auth0\Exception\InvalidApplicationException;
use Bitmotion\Auth0\Service\RedirectService;
use Bitmotion\Auth0\Utility\ApiUtility;
use Bitmotion\Auth0\Utility\ConfigurationUtility;
use Bitmotion\Auth0\Utility\RoutingUtility;
use Bitmotion\Auth0\Utility\UserUtility;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;
use TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException;

class LoginController extends ActionController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var Application
     */
    protected $application;

    protected $error = '';

    protected $errorDescription = '';

    /**
     * @throws InvalidConfigurationTypeException
     */
    public function initializeAction()
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
    }

    /**
     * @todo: Refactor
     *
     * @throws \Exception
     * @throws CoreException
     * @throws InvalidApplicationException
     */
    public function formAction()
    {
        // Get Auth0 user from session storage
        $sessionStore = new SessionStore();
        $userInfo = $sessionStore->get('user');
        $feUserAuthentication = $GLOBALS['TSFE']->fe_user;
        $redirectService = GeneralUtility::makeInstance(RedirectService::class, $this->settings);

        // Redirect when user just logged in (and update him)
        if (GeneralUtility::_GP('logintype') === 'login' && $feUserAuthentication->user !== null && $userInfo !== null) {
            if (!empty(GeneralUtility::_GP('referrer'))) {
                $this->logger->notice('Handle referrer redirect prior to updating user.');
                $redirectService->forceRedirectByReferrer(['logintype' => 'login']);
            }

            GeneralUtility::makeInstance(UserUtility::class)->updateUser($this->getAuthenticationApi(), (int)$this->settings['application']);
            $redirectService->handleRedirect(['groupLogin', 'userLogin', 'login', 'getpost', 'referrer']);
        }

        // Force redirect due to Auth0 sign up or log in errors
        if (!empty(GeneralUtility::_GET('referrer')) && $this->error === AuthenticationApi::ERROR_UNAUTHORIZED) {
            $this->logger->notice('Handle referrer redirect because of Auth0 errors.');
            $redirectService->forceRedirectByReferrer([
                'error' => $this->error,
                'error_description' => $this->errorDescription,
            ]);
        }

        $this->view->assignMultiple([
            'userInfo' => $userInfo,
            'auth0Error' => $this->error,
            'auth0ErrorDescription' => $this->errorDescription,
        ]);
    }

    /**
     * @throws CoreException
     * @throws InvalidApplicationException
     * @throws StopActionException
     * @throws UnsupportedRequestTypeException
     */
    public function loginAction()
    {
        // Get Auth0 user from session storage
        $store = new SessionStore();
        $userInfo = $store->get('user');

        if ($userInfo === null) {
            // Try to login user
            $this->logger->notice('Try to login user.');
            GeneralUtility::makeInstance(UserUtility::class)->loginUser($this->getAuthenticationApi());
        }

        // Show login form
        $this->redirect('form');
    }

    /**
     * @throws CoreException
     * @throws InvalidApplicationException
     * @throws StopActionException
     * @throws UnsupportedRequestTypeException
     */
    public function logoutAction()
    {
        GeneralUtility::makeInstance(UserUtility::class)->logoutUser($this->getAuthenticationApi());
        $this->redirect('form');
    }

    /**
     * @throws CoreException
     * @throws InvalidApplicationException
     */
    protected function getAuthenticationApi(): AuthenticationApi
    {
        $apiUtility = GeneralUtility::makeInstance(ApiUtility::class);
        $routingUtility = GeneralUtility::makeInstance(RoutingUtility::class);
        $callbackUri = $routingUtility->getCallbackUri($this->settings['frontend']['callback'], (int)$this->settings['application']);

        return $apiUtility->getAuthenticationApi((int)$this->settings['application'], $callbackUri);
    }
}
