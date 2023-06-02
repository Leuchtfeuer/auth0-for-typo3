<?php

/*
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

namespace Leuchtfeuer\Auth0\Controller;

use Leuchtfeuer\Auth0\Domain\Model\Application;
use Leuchtfeuer\Auth0\Domain\Transfer\EmAuth0Configuration;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;

class ApplicationController extends BackendController
{
    /**
     * @throws RouteNotFoundException
     */
    public function listAction(): void
    {
        $pid = $this->getStoragePage();
        $this->view->assignMultiple([
            'applications' => $this->applicationRepository->findAll(),
            'pid' => $pid,
            'directory' => BackendUtility::getRecord('pages', $pid),
            'returnUrl' => $this->getModuleUrl(false),
        ]);
    }

    /**
     * @param Application $application
     *
     * @throws StopActionException
     */
    public function deleteAction(Application $application): void
    {
        $this->applicationRepository->remove($application);
        $this->addFlashMessage($this->getTranslation('message.application.deleted.text'), $this->getTranslation('message.application.deleted.title'));
        $this->redirect('list');
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
