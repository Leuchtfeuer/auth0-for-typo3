<?php

/*
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) Leuchtfeuer Digital Marketing <dev@Leuchtfeuer.com>
 */

namespace Leuchtfeuer\Auth0\Controller;

use Leuchtfeuer\Auth0\Domain\Model\Application;
use Leuchtfeuer\Auth0\Domain\Transfer\EmAuth0Configuration;
use Leuchtfeuer\Auth0\Utility\ModeUtility;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;

class ApplicationController extends BackendController
{
    /**
     * @throws RouteNotFoundException
     */
    public function listAction(): ResponseInterface
    {
        $moduleTemplate = $this->initView();
        $pid = $this->getStoragePage();
        $this->view->assignMultiple([
            'applications' => $this->applicationRepository->findAll(),
            'pid' => $pid,
            'directory' => BackendUtility::getRecord('pages', $pid),
        ]);
        if (!ModeUtility::isTYPO3V12()) {
            $this->view->assign('returnUrl', $this->getModuleUrl(false));
        }
        $moduleTemplate->setContent($this->view->render());

        return $this->htmlResponse($moduleTemplate->renderContent());
    }

    /**
     * @throws StopActionException
     */
    public function deleteAction(Application $application): ResponseInterface
    {
        $this->applicationRepository->remove($application);
        $this->addFlashMessage($this->getTranslation('message.application.deleted.text'), $this->getTranslation('message.application.deleted.title'));

        return $this->redirect('list');
    }

    protected function getStoragePage(): int
    {
        $configuration = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK, 'auth0');
        $storagePage = (int)($configuration['persistence']['storagePid'] ?? 0);

        if ($this->pageExists($storagePage)) {
            return $storagePage;
        }

        $storagePage = (new EmAuth0Configuration())->getUserStoragePage();

        return $this->pageExists($storagePage) ? $storagePage : 0;
    }

    protected function pageExists(int $storagePage): bool
    {
        return $storagePage > 0 && BackendUtility::getRecord('pages', $storagePage) !== null;
    }
}
