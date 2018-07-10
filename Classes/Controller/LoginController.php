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

use Bitmotion\Auth0\Api\AuthenticationApi;
use Bitmotion\Auth0\Api\ManagementApi;
use Bitmotion\Auth0\Domain\Model\Application;
use Bitmotion\Auth0\Domain\Repository\ApplicationRepository;
use Bitmotion\Auth0\Service\RedirectService;
use Bitmotion\Auth0\Utility\UpdateUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

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
     * @var AuthenticationApi
     */
    protected $authenticationApi = null;

    /**
     * @var ManagementApi
     */
    protected $managementApi = null;

    /**
     * @var Application
     */
    protected $application = null;

    /**
     * @var string
     */
    protected $uri = '';

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
                $this->managementApi = GeneralUtility::makeInstance(ManagementApi::class, $application);
            } else {
                throw new \Exception(sprintf('No Application found for given id %s', $applicationUid), 1526046354);
            }
        } else {
            throw new \Exception('No Application configured.', 1526046434);
        }
    }

    /**
     * @throws \Auth0\SDK\Exception\CoreException
     */
    public function formAction()
    {
        $this->uri = GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
        $this->authenticationApi = new AuthenticationApi($this->application, $this->uri . 'index.php?id=5&logintype=login', 'openid profile read:current_user', []);

        try {
            $userInfo = $this->authenticationApi->getUser();
            if (GeneralUtility::_GP('logintype') === 'login' && !empty($GLOBALS['TSFE']->fe_user->user)) {
                $this->updateUser($userInfo);
                $this->handleRedirect(['groupLogin', 'userLogin', 'login', 'getpost', 'referrer']);
            }
            $this->view->assign('userInfo', $userInfo);
            $this->view->assign('user', $GLOBALS['TSFE']->fe_user->user);
        } catch (\Exception $exception) {
            $this->authenticationApi->deleteAllPersistentData();
        }
    }

    /**
     * @param array $userInfo
     *
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     * @throws \Exception
     */
    protected function updateUser(array $userInfo)
    {
        $auth0User = $this->managementApi->getUserById($userInfo['sub']);

        if (strtotime($auth0User['updated_at']) > $GLOBALS['TSFE']->fe_user->user['tstamp']) {
            $updateUtility = GeneralUtility::makeInstance(UpdateUtility::class, 'fe_users', $auth0User);
            $updateUtility->updateUser();
        }
    }

    /**
     */
    public function loginAction()
    {
        try {
            $this->uri = GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
            $this->authenticationApi = new AuthenticationApi($this->application, $this->uri . 'index.php?id=5&logintype=login', 'openid profile read:current_user', []);

            $userInfo = $this->authenticationApi->getUser();
            if (!$userInfo) {
                // Try to login user to Auth0
                $this->authenticationApi->login();
            } else {
                // Show login form
                $this->view->assign('userInfo', $userInfo);
            }
        } catch (\Exception $exception) {
            $this->authenticationApi->deleteAllPersistentData();
        }
    }

    /**
     * @throws \Auth0\SDK\Exception\CoreException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     */
    public function logoutAction()
    {
        $this->uri = GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
        $this->authenticationApi = new AuthenticationApi($this->application, $this->uri . 'index.php?id=5&logintype=login', 'openid profile read:current_user', []);
        $this->authenticationApi->logout();
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
}