<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Api;

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

class Authorization
{
    /**
     * Builds and returns the `/authorize` url in order to initialize a new
     * authN/authZ transaction
     *
     * @param string $response_type
     * @param string $redirect_uri
     * @param string $connection        [optional]
     * @param string $state             [optional]
     * @param array  $additional_params [optional]
     *
     * @return string
     *
     * @see https://auth0.com/docs/api/authentication#!#get--authorize_db
     */
    public function get_authorize_link(
        $response_type,
        $redirect_uri,
        $connection = null,
        $state = null,
        $additional_params = []
    ) {
        $additional_params['response_type'] = $response_type;
        $additional_params['redirect_uri'] = $redirect_uri;
        $additional_params['client_id'] = $this->client_id;

        if ($connection !== null) {
            $additional_params['connection'] = $connection;
        }

        if ($state !== null) {
            $additional_params['state'] = $state;
        }

        $query_string = Psr7\build_query($additional_params);

        return "https://{$this->domain}/authorize?{$query_string}";
    }
}
