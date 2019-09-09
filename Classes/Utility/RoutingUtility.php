<?php
declare(strict_types=1);
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

    public function setCallback(array $callbackSettings)
    {
        $pageType = (int)$callbackSettings['targetPageType'] ?? 0;
        $pageUid = (int)$callbackSettings['targetPageUid'] ?? 0;

        if ($pageUid !== 0) {
            // Check whether page exists
            $page = GeneralUtility::makeInstance(ObjectManager::class)->get(PageRepository::class)->checkRecord('pages', $pageUid);

            if (!empty($page)) {
                $this->setTargetPage($pageUid);
            } else {
                $this->logger->warning(sprintf('No page found for given uid "%s".', $pageUid));
            }
        }

        if ($pageType !== 0) {
            $this->setTargetPageType((int)$pageType);
        }
    }

    public function getLogoutUri(string $controllerName, string $actionName, array $callbackSettings): string
    {
        $this->setCallback($callbackSettings);
        $this->setArguments([
            'tx_auth0_loginform' => [
                'action' => $actionName,
                'Controller' => $controllerName,
            ],
            'logintype' => 'logout',
        ]);

        return $this->getUri();
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

            return $uri;
        }

        return '';
    }

    public function setTargetPage(int $targetPage): void
    {
        $this->logger->debug(sprintf('[URI] Set target page to "%s"', $targetPage));
        $this->targetPage = $targetPage;
    }

    public function setTargetPageType(int $targetPageType): void
    {
        $this->logger->debug(sprintf('[URI] Set target page type to "%s"', $targetPageType));
        $this->targetPageType = $targetPageType;
    }

    public function setArguments(array $arguments): void
    {
        $this->logger->debug('[URI] Set arguments', $arguments);
        $this->arguments = $arguments;
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
