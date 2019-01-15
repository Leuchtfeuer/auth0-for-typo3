<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Utility;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Object\ObjectManager;

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
            return $uriBuilder->buildFrontendUri();
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
