<?php
declare(strict_types = 1);
namespace Bitmotion\Auth0\LinkHandling;

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

use TYPO3\CMS\Core\LinkHandling\LinkHandlingInterface;

class Auth0LinkHandler implements LinkHandlingInterface
{
    /**
     * The Base URN for this link handling to act on
     *
     * @var string
     */
    protected $baseUrn = 't3://auth0';

    /**
     * Returns all valid parameters for linking to a TYPO3 page as a string
     *
     * @param array $parameters
     * @return string
     * @throws \InvalidArgumentException
     */
    public function asString(array $parameters): string
    {
        if (empty($parameters['uid'])) {
            throw new \InvalidArgumentException('The Auth0LinkHandler expects an uid as $parameter configuration.', 1586253406);
        }

        return sprintf('%s?uid=%d', $this->baseUrn, (int)$parameters['uid']);
    }

    /**
     * Returns all relevant information built in the link to a page (see asString())
     *
     * @param array $data
     * @return array
     * @throws \InvalidArgumentException
     */
    public function resolveHandlerData(array $data): array
    {
        if (empty($data['uid'])) {
            throw new \InvalidArgumentException('The RecordLinkHandler expects an uid as $data configuration', 1586253419);
        }

        return $data;
    }
}
