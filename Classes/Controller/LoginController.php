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

use Auth0\SDK\Store\SessionStore;
use Bitmotion\Auth0\Api\AuthenticationApi;
use Bitmotion\Auth0\Domain\Model\Application;
use Bitmotion\Auth0\Domain\Repository\ApplicationRepository;
use Bitmotion\Auth0\Service\RedirectService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;

/**
 * Class LoginController
 * @package Bitmotion\Auth0\Controller
 */
class LoginController extends ActionController
{
    /**
     * @var \Bitmotion\Auth0\Domain\Repository\ApplicationRepository
     */
    protected $applicationRepository = null;

    /**
     * @var Application
     */
    protected $application = null;

    /**
     * @param ApplicationRepository $applicationRepository
     */
    public function injectApplicationRepository(ApplicationRepository $applicationRepository)
    {
        $this->applicationRepository = $applicationRepository;
    }

    /**
     * @throws \Exception
     */
    public function initializeAction()
    {
        if (isset($this->settings['application']) && !empty($this->settings['application'])) {
            $applicationUid = $this->settings['application'];
            $application = $this->applicationRepository->findByIdentifier((int)$applicationUid);

            if ($application instanceof Application) {
                $this->application = $application;
            } else {
                throw new \Exception(sprintf('No Application found for given id %s', $applicationUid), 1526046354);
            }
        } else {
            throw new \Exception('No Application configured.', 1526046434);
        }
    }

    /**
     * form action
     */
    public function formAction()
    {
        // Get Auth0 user from session storage
        $store = new SessionStore();
        $userInfo = $store->get('user');

        // Redirect user on login
        if (GeneralUtility::_GP('logintype') === 'login' && !empty($GLOBALS['TSFE']->fe_user->user) && $userInfo !== null) {
            $this->handleRedirect(['groupLogin', 'userLogin', 'login', 'getpost', 'referrer']);
        }

        $this->view->assign('userInfo', $userInfo);
    }

    /**
     * login action
     */
    public function loginAction()
    {
        // Get Auth0 user from session storage
        $store = new SessionStore();
        $userInfo = $store->get('user');

        if ($userInfo === null) {
            try {
                $authenticationApi = new AuthenticationApi($this->application, $this->getUri(), 'openid profile read:current_user', []);
                $userInfo = $authenticationApi->getUser();

                if (!$userInfo) {
                    // Try to login user to Auth0
                    $authenticationApi->login();
                } else {
                    // Show login form
                    $this->redirect('form');
                }
            } catch (\Exception $exception) {
                if (isset($authenticationApi) && $authenticationApi instanceof AuthenticationApi) {
                    $authenticationApi->deleteAllPersistentData();
                }
            }
        }
    }

    /**
     * @throws \Auth0\SDK\Exception\CoreException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     */
    public function logoutAction()
    {
        $authenticationApi = new AuthenticationApi(
            $this->application,
            $this->getUri(),
            'openid profile read:current_user',
            []
        );

        $authenticationApi->logout();
        $this->redirect('form');
    }

    /**
     * @param array $allowedRedirectMethods
     */
    protected function handleRedirect(array $allowedRedirectMethods)
    {
        if ((bool)$this->settings['redirectDisable'] === false && !empty($this->settings['redirectMode'])) {
            $redirectService = GeneralUtility::makeInstance(RedirectService::class, $this->settings);
            $redirectUris = $redirectService->getRedirectUri($allowedRedirectMethods);
            if (!empty($redirectUris)) {
                header('Location: '. $redirectService->getUri($redirectUris), false, 307);
            }
        }
    }

    /**
     * @return string
     */
    protected function getUri()
    {
        return
            $this->objectManager->get(UriBuilder::class)
                ->reset()
                ->setTargetPageUid($GLOBALS['TSFE']->id)
                ->setArguments([
                    'logintype' => 'login',
                    'application' => $this->application->getUid()
                ])->setCreateAbsoluteUri(true)
                ->setUseCacheHash(false)
                ->buildFrontendUri();
    }
}