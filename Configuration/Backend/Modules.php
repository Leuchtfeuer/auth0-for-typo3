<?php

use Leuchtfeuer\Auth0\Controller\ApplicationController;
use Leuchtfeuer\Auth0\Controller\BackendController;
use Leuchtfeuer\Auth0\Controller\PropertyController;
use Leuchtfeuer\Auth0\Controller\RoleController;

return [
    'Auth0' => [
        'parent' => 'system',
        'position' => ['after' => 'web_info'],
        'access' => 'admin',
        'workspaces' => 'live',
        'iconIdentifier' => 'EXT:auth0/Resources/Public/Icons/Module.svg',
        'path' => '/module/system/auth0',
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