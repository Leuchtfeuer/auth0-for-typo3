<?php

declare(strict_types=1);

use Leuchtfeuer\Auth0\Controller\ApplicationController;
use Leuchtfeuer\Auth0\Controller\BackendController;
use Leuchtfeuer\Auth0\Controller\PropertyController;
use Leuchtfeuer\Auth0\Controller\RoleController;

return [
    'Auth0' => [
        'parent' => 'admin',
        'access' => 'admin',
        'workspaces' => 'live',
        'iconIdentifier' => 'moduleAuth0',
        'path' => '/module/admin/auth0',
        'labels' => 'LLL:EXT:auth0/Resources/Private/Language/locallang_mod.xlf',
        'extensionName' => 'auth0',
        'controllerActions' => [
            BackendController::class => 'list',
            ApplicationController::class => 'list,delete',
            RoleController::class => 'list,update',
            PropertyController::class => 'list,new,create,edit,update,delete',
        ],
    ],
];
