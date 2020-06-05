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

namespace Bitmotion\Auth0\Api\Management;

use Auth0\SDK\API\Header\ContentType;
use Auth0\SDK\Exception\ApiException;
use Auth0\SDK\Exception\CoreException;
use Bitmotion\Auth0\Domain\Model\Auth0\Api\Client;
use Symfony\Component\VarExporter\Exception\ClassNotFoundException;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class BlacklistApi extends GeneralManagementApi
{
    /**
     * Retrieves the jti and aud of all tokens that are blacklisted.
     * The JWT spec provides the jti field as a way to prevent replay attacks. Though Auth0 tokens do not currently return a jti,
     * you can blacklist a jti to prevent a token being used more than X times. In this way you are kind of implementing a nonce
     * (think of the token's signature as the nonce). If a token gets stolen, it should be blacklisted (or the nth token that has
     * been issued after it) and wait for it to expire.
     * Required scope: "blacklist:tokens"
     *
     *
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @return object|ObjectStorage
     * @see https://auth0.com/docs/api/management/v2#!/Blacklists/get_tokens
     */
    public function get(string $aud)
    {
        $response = $this->client
            ->request(Client::METHOD_GET)
            ->addPath('blacklists')
            ->addPath('tokens')
            ->withParam('aud', $aud)
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * Adds the token identified by the jti to a blacklist for the tenant.
     * The JWT spec provides the jti field as a way to prevent replay attacks. Though Auth0 tokens do not currently return a jti,
     * you can blacklist a jti to prevent a token being used more than X times. In this way you are kind of implementing a nonce
     * (think of the token's signature as the nonce). If a token gets stolen, it should be blacklisted (or the nth token that has
     * been issued after it) and wait for it to expire.
     * Required scope: "blacklist:tokens"
     *
     *
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @return object|ObjectStorage
     * @see https://auth0.com/docs/api/management/v2#!/Blacklists/post_tokens
     */
    public function add(string $jti, string $aud = '')
    {
        $body = [
            'jti' => $jti,
        ];

        $this->addStringProperty($body, 'aud', $aud);

        $response = $this->client
            ->request(Client::METHOD_POST)
            ->addPath('blacklists')
            ->addPath('tokens')
            ->withHeader(new ContentType('application/json'))
            ->withBody(\GuzzleHttp\json_encode($body))
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }
}
