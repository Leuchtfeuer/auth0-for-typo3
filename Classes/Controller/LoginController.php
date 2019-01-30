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
use Bitmotion\Auth0\Api\ManagementApi;
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

        if (GeneralUtility::_GP('logintype') === 'login' && $feUserAuthentication->user !== null && $userInfo !== null) {
            if (!empty(GeneralUtility::_GP('referrer'))) {
                $this->logger->notice('Handle referrer redirect prior to updating user.');
                $this->settings['redirectDisable'] = false;
                $this->settings['redirectMode'] = 'referrer';
                $this->handleRedirect(['referrer'], ['logintype' => 'login']);
            }

            // Try to update user
            $this->logger->notice('Update User due to login.');
            GeneralUtility::makeInstance(UserUtility::class)->updateUser($this->getAuthenticationApi(), (int)$this->settings['application']);

            // Redirect user on login
            $this->handleRedirect(['groupLogin', 'userLogin', 'login', 'getpost', 'referrer']);
            $this->logger->notice('No redirect configured. Showing form.');
        }

        if ($userInfo === null && $feUserAuthentication->user !== null) {
            $this->logger->notice('Found active TYPO3 session but no active Auth0 session.');
            $applicationUid = (!empty(GeneralUtility::_GP('application'))) ? GeneralUtility::_GP('application') : $this->settings['application'];
            $managementApi = GeneralUtility::makeInstance(ManagementApi::class, (int)$applicationUid);
            $auth0User = $managementApi->getUserById($feUserAuthentication->user['auth0_user_id']);

            if (isset($auth0User['blocked']) && $auth0User['blocked'] === true) {
                $this->logger->notice('Logoff user as it is blocked in Auth0.');
            } else {
                $this->logger->debug('Map raw auth0 user to token info array.');
                $userInfo = GeneralUtility::makeInstance(UserUtility::class)->convertAuth0UserToUserInfo($auth0User);
                $sessionStore->set('user', $userInfo);
            }
        }

        $auth0ErrorCode = GeneralUtility::_GET('error');
        if (!empty(GeneralUtility::_GET('referrer')) && $auth0ErrorCode === AuthenticationApi::ERROR_UNAUTHORIZED) {
            $this->logger->notice('Handle referrer redirect prior to updating user.');
            $this->settings['redirectDisable'] = false;
            $this->settings['redirectMode'] = 'referrer';
            $this->handleRedirect(
                ['referrer'],
                [
                    'error' => $this->error,
                    'error_description' => $this->errorDescription,
                ]
            );
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
     * @todo: Move into other class
     */
    protected function handleRedirect(array $allowedMethods, array $additionalParameters = [])
    {
        if ((bool)$this->settings['redirectDisable'] === false && !empty($this->settings['redirectMode'])) {
            $this->logger->notice('Try to redirect user.');
            $redirectService = GeneralUtility::makeInstance(RedirectService::class, $this->settings);
            $redirectUris = $redirectService->getRedirectUri($allowedMethods);

            if (!empty($redirectUris)) {
                $redirectUri = $this->addAdditionalParamsToRedirectUri($redirectService->getUri($redirectUris), $additionalParameters);
                $this->logger->notice(sprintf('Redirect to: %s', $redirectUri));
                header('Location: ' . $redirectUri, false, 307);
                die;
            }

            $this->logger->warning('Redirect failed.');
        }
    }

    /**
     * @todo: Move into other class
     */
    protected function addAdditionalParamsToRedirectUri(string $uri, array $additionalParams): string
    {
        if (!empty($additionalParams)) {
            $uri .= '?';
        }

        foreach ($additionalParams as $key => $value) {
            $uri .= $key . '=' . $value . '&';
        }

        return rtrim($uri, '&');
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
