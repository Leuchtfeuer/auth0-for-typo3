<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Utility;

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

use Bitmotion\Auth0\Domain\Model\Application;
use Bitmotion\Auth0\Domain\Repository\ApplicationRepository;
use Bitmotion\Auth0\Exception\InvalidApplicationException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class ApplicationUtility
{
    /**
     * @throws InvalidApplicationException
     */
    public static function getApplication(int $applicationUid): Application
    {
        if ($applicationUid !== 0) {
            $applicationRepository = GeneralUtility::makeInstance(ObjectManager::class)->get(ApplicationRepository::class);
            $application = $applicationRepository->findByIdentifier((int)$applicationUid);

            if ($application instanceof Application) {
                return $application;
            }
            throw new InvalidApplicationException(sprintf('No Application found for given id %s', $applicationUid), 1526046354);
        }
        throw new InvalidApplicationException('No Application configured.', 1526046434);
    }
}
