<?php
defined('TYPO3_MODE') || die();

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms']['db_new_content_el']['wizardItemsHook'][] = 'EXT:auth0/Classes/Hooks/WizardItemsHook.php:' . \Bitmotion\Auth0\Hooks\WizardItemsHook::class;

$iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
$iconRegistry->registerIcon(
    'auth0',
    \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class, array(
    'source' => 'EXT:auth0/Resources/Public/Icons/auth0.svg'
));