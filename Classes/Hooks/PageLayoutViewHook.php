<?php
declare(strict_types = 1);
namespace Bitmotion\Auth0\Hooks;

/***
 *
 * This file is part of the "Auth0 for TYPO3" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2018 Florian Wessels <f.wessels@bitmotion.de>, Bitmotion GmbH
 *
 ***/
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

class PageLayoutViewHook
{
    /**
     * @var string
     */
    protected $listType = '';

    /**
     * @var array
     */
    protected $flexFormData = [];

    public function getSummary(array $params): string
    {
        $this->listType = $params['row']['list_type'];
        $this->flexFormData = GeneralUtility::xml2array($params['row']['pi_flexform']);

        $header = '<p><strong>' . 'Auth0: ' . $this->getLanguageService()->sL('LLL:EXT:auth0/Resources/Private/Language/locallang_be.xlf:plugin.loginForm.title') . '</strong></p>';
        $content = '';

        if (!empty($this->flexFormData)) {
            $content = '<strong>';
            $content .= $this->getLanguageService()->sL('LLL:EXT:auth0/Resources/Private/Language/Database.xlf:tx_auth0_domain_model_application');
            $content .= ':';
            $content .= '</strong>';
            $content .= '<span style="padding-left: 15px">' . $this->getApplicationName() . '</span>';
        }

        return $header . $content;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    protected function getApplicationName(): string
    {
        $applicationUid = $this->getFieldFromFlexForm('settings.application');

        if (empty($applicationUid)) {
            return 'Not defined';
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_auth0_domain_model_application');

        return (string)$queryBuilder
            ->select('title')
            ->from('tx_auth0_domain_model_application')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($applicationUid, \PDO::PARAM_INT)
                )
            )->execute()
            ->fetchColumn();
    }

    protected function getFieldFromFlexForm(string $key, string $sheet = 'sDEF'): string
    {
        $flexForm = $this->flexFormData;

        if (isset($flexForm['data'])) {
            $flexForm = $flexForm['data'];
            if (is_array($flexForm) && is_array($flexForm[$sheet]) && is_array($flexForm[$sheet]['lDEF']) && is_array($flexForm[$sheet]['lDEF'][$key]) && isset($flexForm[$sheet]['lDEF'][$key]['vDEF'])) {
                return $flexForm[$sheet]['lDEF'][$key]['vDEF'];
            }
        }

        return '';
    }
}
