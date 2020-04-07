<?php
declare(strict_types = 1);
namespace Bitmotion\Auth0\LinkHandler;

/***
 *
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2020 Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 *
 ***/

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\LinkHandling\Exception\UnknownLinkHandlerException;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Recordlist\LinkHandler\RecordLinkHandler;

class Auth0LinkHandler extends RecordLinkHandler
{
    /**
     * Checks if this is the right handler for the given link.
     *
     * Also stores information locally about currently linked record.
     *
     * @param array $linkParts Link parts as returned from TypoLinkCodecService
     * @return bool
     */
    public function canHandleLink(array $linkParts): bool
    {
        if (!$linkParts['url'] || !$linkParts['url']['uid']) {
            return false;
        }

        // Get the related record
        $record = BackendUtility::getRecord('tx_auth0_domain_model_link', $linkParts['url']['uid']);

        if ($record === null) {
            $linkParts['title'] = $this->getLanguageService()->getLL('recordNotFound');
        } else {
            $linkParts['tableName'] = $this->getLanguageService()->sL($GLOBALS['TCA']['tx_auth0_domain_model_link']['ctrl']['title']);
            $linkParts['pid'] = (int)$record['pid'];
            $linkParts['title'] = $linkParts['title'] ?: BackendUtility::getRecordTitle('tx_auth0_domain_model_link', $record);
        }

        $linkParts['url']['type'] = $linkParts['type'];
        $this->linkParts = $linkParts;

        return true;
    }

    /**
     * Returns attributes for the body tag.
     *
     * @return string[] Array of body-tag attributes
     * @throws UnknownLinkHandlerException
     */
    public function getBodyTagAttributes(): array
    {
        $attributes = [
            'data-identifier' => 't3://auth0?uid=',
        ];
        if (!empty($this->linkParts)) {
            $attributes['data-current-link'] = GeneralUtility::makeInstance(LinkService::class)->asString($this->linkParts['url']);
        }

        return $attributes;
    }
}
