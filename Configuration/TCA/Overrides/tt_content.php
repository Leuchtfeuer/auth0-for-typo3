<?php
declare(strict_types=1);

/////////////////
//   PLUGINS   //
/////////////////

// Register LoginForm PlugIn
if (version_compare(TYPO3_version, '10.0.0', '>=')) {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
        'Auth0',
        'LoginForm',
        'Auth0: Login form'
    );
} else {
    // TODO: Remove this when dropping TYPO3 9 LTS support.
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
        'Bitmotion.Auth0',
        'LoginForm',
        'Auth0: Login form'
    );
}

///////////////////
//   FLEXFORMS   //
///////////////////

// LoginForm
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['auth0_loginform'] = 'pi_flexform';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['auth0_loginform'] = 'layout,select_key,pages,recursive';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    'auth0_loginform',
    'FILE:EXT:auth0/Configuration/FlexForms/LoginForm.xml'
);
