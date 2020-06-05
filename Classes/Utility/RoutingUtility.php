<?php

declare(strict_types=1);

/*
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

namespace Bitmotion\Auth0\Utility;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Frontend\Page\PageRepository;

class RoutingUtility implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected $targetPage = 0;

    protected $targetPageType = 0;

    protected $arguments = [];

    protected $createAbsoluteUri = true;

    protected $buildFrontendUri = true;

    public function __construct()
    {
        $this->targetPage = (int)$GLOBALS['TSFE']->id;
    }

    /**
     * @deprecated Use PSR-15 Middleware instead.
     */
    public function setCallback(int $pageUid, int $pageType): self
    {
        if ($pageUid !== 0) {
            // Check whether page exists
            if (class_exists('TYPO3\\CMS\\Core\\Domain\\Repository\\PageRepository')) {
                $page = GeneralUtility::makeInstance(ObjectManager::class)->get('TYPO3\\CMS\\Core\\Domain\\Repository\\PageRepository')->checkRecord('pages', $pageUid);
            } else {
                // TODO: Remove this when dropping TYPO3 9 LTS support.
                $page = GeneralUtility::makeInstance(ObjectManager::class)->get(PageRepository::class)->checkRecord('pages', $pageUid);
            }

            if (!empty($page)) {
                $this->setTargetPage($pageUid);
            } else {
                $this->logger->warning(sprintf('No page found for given uid "%s".', $pageUid));
            }
        }

        if ($pageType !== 0) {
            $this->setTargetPageType($pageType);
        }

        return $this;
    }

    public function getUri(): string
    {
        $uriBuilder = GeneralUtility::makeInstance(ObjectManager::class)->get(UriBuilder::class);
        $uriBuilder
            ->reset()
            ->setTargetPageUid($this->targetPage)
            ->setTargetPageType($this->targetPageType)
            ->setArguments($this->arguments);

        if ($this->createAbsoluteUri) {
            $uriBuilder->setCreateAbsoluteUri($this->createAbsoluteUri);
        }

        if ($this->buildFrontendUri) {
            $uri = $uriBuilder->buildFrontendUri();
            $this->logger->notice(sprintf('Set URI to: %s', $uri));

            // Base of site configuration might be "/" so we have to prepend the domain
            if (strpos($uri, '/') === 0) {
                $uri = GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . ltrim($uri, '/');
            }

            return $uri;
        }

        return '';
    }

    public function setTargetPage(int $targetPage): self
    {
        $this->logger->debug(sprintf('[URI] Set target page to "%s"', $targetPage));
        $this->targetPage = $targetPage;

        return $this;
    }

    public function setTargetPageType(int $targetPageType): void
    {
        $this->logger->debug(sprintf('[URI] Set target page type to "%s"', $targetPageType));
        $this->targetPageType = $targetPageType;
    }

    public function addArgument(string $key, $value): self
    {
        $this->arguments = array_merge_recursive($this->arguments, [ $key => $value ]);

        return $this;
    }

    public function setArguments(array $arguments): self
    {
        $this->logger->debug('[URI] Set arguments', $arguments);
        $this->arguments = $arguments;

        return $this;
    }

    public function setCreateAbsoluteUri(bool $createAbsoluteUri): void
    {
        $this->createAbsoluteUri = $createAbsoluteUri;
    }

    public function setBuildFrontendUri(bool $buildFrontendUri): void
    {
        $this->buildFrontendUri = $buildFrontendUri;
    }
}
