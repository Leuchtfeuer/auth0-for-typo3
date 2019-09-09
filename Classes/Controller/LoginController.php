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
use Bitmotion\Auth0\Api\Auth0;
use Bitmotion\Auth0\Domain\Repository\ApplicationRepository;
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
     * @throws \Exception
     * @throws CoreException
     * @throws InvalidApplicationException
     */
    public function formAction()
    {
        // Get Auth0 user from session storage
        // ToDo: User Auth0->getUser() instead.
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

            GeneralUtility::makeInstance(UserUtility::class)->updateUser($this->getAuth0(), (int)$this->settings['application']);
            $redirectService->handleRedirect(['groupLogin', 'userLogin', 'login', 'getpost', 'referrer']);
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
        // ToDo: User Auth0->getUser() instead.
        $store = new SessionStore();
        $userInfo = $store->get('user');

        if ($userInfo === null) {
            // Try to login user
            $this->logger->notice('Try to login user.');

            $_params = ['sessionStore' => $store];

            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['auth0']['login_pre_processing'] ?? [] as $_funcRef) {
                if ($_funcRef) {
                    GeneralUtility::callUserFunction($_funcRef, $_params, $this);
                }
            }

            $this->logger->notice('Try to login user to Auth0.');
            $this->getAuth0()->login();
        }

        // Show login form
        $this->redirect('form');
    }

    /**
     * @throws CoreException
     * @throws InvalidApplicationException
     * @throws StopActionException
     * @throws UnsupportedRequestTypeException
     * @deprecated
     * TODO: Write Hook $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['logoff_post_processing'] instead (or pre_processing)
     */
    public function logoutAction()
    {
        $_params = [];
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['auth0']['logoff_pre_processing'] ?? [] as $_funcRef) {
            if ($_funcRef) {
                GeneralUtility::callUserFunction($_funcRef, $_params, $this);
            }
        }

        $this->getAuth0()->logout();

        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['auth0']['logoff_post_processing'] ?? [] as $_funcRef) {
            if ($_funcRef) {
                GeneralUtility::callUserFunction($_funcRef, $_params, $this);
            }
        }

        $routingUtility = GeneralUtility::makeInstance(RoutingUtility::class);

        $redirectUri = $routingUtility->getLogoutUri(
            $this->request->getControllerName() ?? 'Login',
            $this->request->getControllerActionName() ?? 'form',
            $this->settings['frontend']['callback'] ?? []
        );

        if ((bool)$this->settings['softLogout'] === true) {
            $this->redirectToUri($redirectUri);
        }

        $application = GeneralUtility::makeInstance(ApplicationRepository::class)->findByUid((int)$this->settings['application']);
        $logoutUri = $this->getAuth0()->getLogoutUri($redirectUri, $application['id']);

        $this->redirectToUri($logoutUri);
    }

    /**
     * @throws CoreException
     * @throws InvalidApplicationException
     */
    protected function getAuth0(): Auth0
    {
        $apiUtility = GeneralUtility::makeInstance(ApiUtility::class, (int)$this->settings['application']);
        $routingUtility = GeneralUtility::makeInstance(RoutingUtility::class);
        $routingUtility->setArguments([
            'logintype' => 'login',
            'application' => (int)$this->settings['application'],
            'referrer' => $routingUtility->getUri(),
        ]);
        $routingUtility->setCallback($this->settings['frontend']['callback']);

        return $apiUtility->getAuth0($routingUtility->getUri());
    }
}
