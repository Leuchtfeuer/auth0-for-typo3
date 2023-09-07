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

use Leuchtfeuer\Auth0\Configuration\Auth0Configuration;
use Leuchtfeuer\Auth0\Domain\Repository\UserGroup\BackendUserGroupRepository;
use Leuchtfeuer\Auth0\Domain\Repository\UserGroup\FrontendUserGroupRepository;
use Leuchtfeuer\Auth0\Domain\Transfer\EmAuth0Configuration;
use Leuchtfeuer\Auth0\Factory\ConfigurationFactory;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class RoleController extends BackendController
{
    public function listAction(): ResponseInterface
    {
        $this->view->assignMultiple([
            'frontendUserGroupMapping' => (new FrontendUserGroupRepository())->findAll(),
            'backendUserGroupMapping' => (new BackendUserGroupRepository())->findAll(),
            'extensionConfiguration' => new EmAuth0Configuration(),
            'yamlConfiguration' => GeneralUtility::makeInstance(Auth0Configuration::class)->load(),
        ]);
        return $this->htmlResponse();
    }

    /**
     * @param string $key
     * @param int $defaultFrontendUserGroup
     * @param string $adminRole
     * @param int $defaultBackendUserGroup
     *
     * @return ResponseInterface
     */
    public function updateAction(
        string $key = 'roles',
        int $defaultFrontendUserGroup = 0,
        string $adminRole = '',
        int $defaultBackendUserGroup = 0
    ): ResponseInterface {
        $auth0Configuration = GeneralUtility::makeInstance(Auth0Configuration::class);
        $configuration = $auth0Configuration->load();

        $configuration['roles'] = (new ConfigurationFactory())->buildRoles(
            $key,
            $defaultFrontendUserGroup,
            $adminRole,
            $defaultBackendUserGroup
        );

        $auth0Configuration->write($configuration);
        $this->addFlashMessage($this->getTranslation('message.role.updated.text'), $this->getTranslation('message.role.updated.title'));
        return $this->redirect('list');
    }
}
