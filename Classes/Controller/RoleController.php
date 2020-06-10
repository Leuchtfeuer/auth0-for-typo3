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

use Bitmotion\Auth0\Configuration\Auth0Configuration;
use Bitmotion\Auth0\Domain\Repository\UserGroup\BackendUserGroupRepository;
use Bitmotion\Auth0\Domain\Repository\UserGroup\FrontendUserGroupRepository;
use Bitmotion\Auth0\Domain\Transfer\EmAuth0Configuration;
use Bitmotion\Auth0\Factory\ConfigurationFactory;
use Bitmotion\Auth0\Utility\ConfigurationUtility;
use TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;

class RoleController extends BackendController
{
    public function listAction(): void
    {
        $this->view->assignMultiple([
            'frontendUserGroupMapping' => (new FrontendUserGroupRepository())->findAll(),
            'backendUserGroupMapping' => (new BackendUserGroupRepository())->findAll(),
            'extensionConfiguration' => new EmAuth0Configuration(),
            'yamlConfiguration' => (new Auth0Configuration())->load(),
        ]);
    }

    /**
     * @param string $key
     * @param int $defaultFrontendUserGroup
     * @param string $adminRole
     * @param int $defaultBackendUserGroup
     *
     * @throws StopActionException
     */
    public function updateAction(
        string $key = 'roles',
        int $defaultFrontendUserGroup = 0,
        string $adminRole = '',
        int $defaultBackendUserGroup= 0
    ): void {
        $auth0Configuration = new Auth0Configuration();
        $configuration = $auth0Configuration->load();

        $configuration['roles'] = (new ConfigurationFactory())->buildRoles(
            $key,
            $defaultFrontendUserGroup,
            $adminRole,
            $defaultBackendUserGroup
        );

        $auth0Configuration->write($configuration);
        $this->addFlashMessage($this->getTranslation('message.role.updated.text'), $this->getTranslation('message.role.updated.title'));
        $this->redirect('list');
    }

    /**
     * @throws InvalidConfigurationTypeException
     * @throws StopActionException
     */
    public function acquireMappingTypoScriptAction(): void
    {
        $settings = ConfigurationUtility::getSetting('roles');
        (new FrontendUserGroupRepository())->translate($settings['fe_users']);
        (new BackendUserGroupRepository())->translate($settings['be_users']);

        $auth0Configuration = new Auth0Configuration();
        $configuration = $auth0Configuration->load();
        $configuration['roles']['key'] = $settings['key'];
        $auth0Configuration->write($configuration);

        $this->addFlashMessage($this->getTranslation('message.role.imported.text'), $this->getTranslation('message.role.imported.title'));
        $this->redirect('list');
    }
}
