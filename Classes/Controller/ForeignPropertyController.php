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
use Bitmotion\Auth0\Factory\ConfigurationFactory;
use Bitmotion\Auth0\Utility\TcaUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;

class ForeignPropertyController extends BackendController
{
    public function selectForeignTableAction(array $property, string $table, string $type): void
    {
        $this->addButton('menu.button.cancel', 'list', 'Property', 'actions-close');

        $tca = new TcaUtility();
        $properties = $tca->getUnusedColumnsFromTable($table, $property['databaseField'], $property['foreignTable']);
        $foreignKeyColumns = $properties;

        $joins = [];
        if (!empty($property['firstJoinTable'])) {
            $joins['firstJoinTable'] = $property['firstJoinTable'];
            $joins['firstJoinColumns'] = $tca->getColumnsFromTable($property['firstJoinTable']);
            $foreignKeyColumns = $joins['firstJoinColumns'];
        }
        if (!empty($property['secondJoinTable'])) {
            $joins['secondJoinTable'] = $property['secondJoinTable'];
            $joins['secondJoinColumns'] = $tca->getColumnsFromTable($property['secondJoinTable']);
            $foreignKeyColumns = $joins['secondJoinColumns'];
        }

        $this->view->assignMultiple([
            'table' => $table,
            'type' => $type,
            'properties' => $tca->getUnusedColumnsFromTable($table, null, $property['foreignTable']),
            'foreignTable' => $property['foreignTable'],
            'joins' => $joins,
            'foreignKey' => $foreignKeyColumns,
        ]);
    }

    public function editAction(array $property, string $table, string $type): void
    {
        $this->addButton('menu.button.cancel', 'list', 'Property', 'actions-close');

        $tca = new TcaUtility();

        $properties = $tca->getUnusedColumnsFromTable($table, $property['databaseField'], $property['foreignTable']);
        $foreignKeyColumns = $properties;

        if (!empty($property['firstJoinTable'])) {
            $joins['firstJoinTable'] = $property['firstJoinTable'];
            $joins['firstJoinColumns'] = $tca->getColumnsFromTable($property['firstJoinTable']);
            $foreignKeyColumns = $joins['firstJoinColumns'];
        }
        if (!empty($property['secondJoinTable'])) {
            $joins['secondJoinTable'] = $property['secondJoinTable'];
            $joins['secondJoinColumns'] = $tca->getColumnsFromTable($property['secondJoinTable']);
            $foreignKeyColumns = $joins['secondJoinColumns'];
        }

        $this->view->assignMultiple([
            'property' => $property,
            'table' => $table,
            'type' => $type,
            'properties' => $properties,
            'foreignTable' => $property['foreignTable'],
            'joins' => $joins,
            'foreignKey' => $foreignKeyColumns
        ]);
    }

    /**
     * @throws StopActionException
     */
    public function createAction(array $property, string $table, string $type): void
    {
        if (empty($property['databaseField']) || empty($property['auth0Property'])) {
            $this->forward('new');
        }

        $propertyConfiguration = (new ConfigurationFactory())
            ->buildProperty(
                $property['auth0Property'],
                $property['databaseField'],
                $property['processing'] ?? 'null',
                $property['foreignTable'] ?? null,
                $property['foreignKey'] ?? null,
                $property['firstJoinTable'] ?? null,
                $property['firstJoinColumn'] ?? null,
                $property['secondJoinTable'] ?? null,
                $property['secondJoinColumn'] ?? null
            );
        $auth0Configuration = GeneralUtility::makeInstance(Auth0Configuration::class);
        $configuration = $auth0Configuration->load();
        $configuration['properties'][$table][$type][] = $propertyConfiguration;
        $auth0Configuration->write($configuration);

        $this->addFlashMessage($this->getTranslation('message.property.created.text'), $this->getTranslation('message.property.created.title'));
        $this->redirect('list', 'Property');
    }

    /**
     * @throws StopActionException
     */
    public function updateAction(array $property, string $table, string $type): void
    {
        $auth0Configuration = GeneralUtility::makeInstance(Auth0Configuration::class);
        $configuration = $auth0Configuration->load();

        foreach ($configuration['properties'][$table][$type] ?? [] as $key => $item) {
            if ($item['databaseField'] === $property['databaseField']) {
                $configuration['properties'][$table][$type][$key] = $property;
                break;
            }
        }

        $auth0Configuration->write($configuration);
        $this->addFlashMessage($this->getTranslation('message.property.updated.text'), $this->getTranslation('message.property.updated.title'));
        $this->redirect('list', 'Property');
    }
}
