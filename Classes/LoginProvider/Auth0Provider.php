<?php
declare(strict_types=1);

namespace Bitmotion\Auth0\LoginProvider;

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
use Bitmotion\Auth0\Domain\Model\Application;
use Bitmotion\Auth0\Domain\Model\Dto\EmAuth0Configuration;
use Bitmotion\Auth0\Domain\Repository\ApplicationRepository;
use TYPO3\CMS\Backend\Controller\LoginController;
use TYPO3\CMS\Backend\LoginProvider\LoginProviderInterface;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Class Auth0Provider
 * @package Bitmotion\Auth0\LoginProvider
 */
class Auth0Provider implements LoginProviderInterface
{
    /**
     * @param StandaloneView  $standaloneView
     * @param PageRenderer    $pageRenderer
     * @param LoginController $loginController
     *
     * @throws \Auth0\SDK\Exception\CoreException
     */
    public function render(StandaloneView $standaloneView, PageRenderer $pageRenderer, LoginController $loginController)
    {
        $standaloneView->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:auth0/Resources/Private/Templates/Backend.html'));

        $configuration = new EmAuth0Configuration();
        $applicationRepository = GeneralUtility::makeInstance(ObjectManager::class)->get(ApplicationRepository::class);
        $application = $applicationRepository->findByUid($configuration->getBackendConnection());

        if ($application instanceof Application) {
            $standaloneView->setLayoutRootPaths(['EXT:auth0/Resources/Private/Layouts']);
            $authenticationApi = new AuthenticationApi($application, GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL') . '&login=1', 'openid profile read:current_user');

            // Logout user
            if (GeneralUtility::_GP('logout') == 1) {
                $authenticationApi->logout();
                $authenticationApi->deleteAllPersistentData();
            }

            try {
                $userInfo = $authenticationApi->getUser();

                if (!$userInfo && GeneralUtility::_GP('login') == 1) {
                    // Try to login user to Auth0
                    $authenticationApi->login();
                } else {
                    // Show login form
                    $standaloneView->assign('userInfo', $userInfo);
                }
            } catch (\Exception $exception) {
                $authenticationApi->deleteAllPersistentData();
            }
        } else {
            $standaloneView->assign('error', 'application');
        }
    }
}