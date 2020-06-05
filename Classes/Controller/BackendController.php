<?php

/*
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

namespace Bitmotion\Auth0\Controller;

use Bitmotion\Auth0\Domain\Repository\UserGroupRepository;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class BackendController extends ActionController
{
    protected $defaultViewObjectName = BackendTemplateView::class;

    public function listAction()
    {
        $this->view->assignMultiple([
            'groupMapping' => (new UserGroupRepository())->findAll(),
        ]);
    }
}
