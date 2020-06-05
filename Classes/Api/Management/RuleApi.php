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

class RuleApi extends GeneralManagementApi
{
    /**
     * Retrieves a list of all rules. Accepts a list of fields to include or exclude.
     * The enabled parameter can be specified to get enabled or disabled rules.
     * The rule's stage of executing could be set to the following values login_success, login_failure or pre_authorize
     * Required scope: "read:rules"
     *
     * @param int    $page          The page number. Zero based.
     * @param int    $perPage       The amount of entries per page. Default: 50. Max value: 100. If not present, pagination will
     *                              be disabled
     * @param bool   $includeTotals true if a query summary must be included in the result
     * @param bool   $enabled       If provided retrieves rules that match the value, otherwise all rules are retrieved
     * @param string $fields        A comma separated list of fields to include or exclude (depending on include_fields) from the
     *                              result, empty to retrieve all fields
     * @param bool   $includeFields true if the fields specified are to be included in the result, false otherwise
     *                              (defaults to true)
     *
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @return object|object[]
     * @see https://auth0.com/docs/api/management/v2#!/Rules/get_rules
     */
    public function list(
        int $page = 0,
        int $perPage = 50,
        bool $includeTotals = false,
        bool $enabled = true,
        string $fields = '',
        bool $includeFields = true
    ) {
        $params = [
            'include_totals' => $includeTotals,
            'enabled' => $enabled,
            'include_fields' => $includeFields,
            'page' => $page,
            'per_page' => $perPage,
        ];

        $this->addStringProperty($params, 'fields', $fields);

        $response = $this->client
            ->request(Client::METHOD_GET)
            ->addPath('rules')
            ->withDictParams($params)
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * Creates a new rule according to the JSON object received in body
     * The samples on the right show you every attribute that could be used. Mandatory attributes are name and script
     * Note: Changing a rule's stage of execution from the default login_success can change the rule's function signature to have
     * user omitted.
     * Required scope: "create:rules"
     *
     * @param string $name   The name of the rule. Can only contain alphanumeric characters, spaces and '-'. Can neither start
     *                       nor end with '-' or spaces
     * @param string $script A script that contains the rule's code
     * @param int    $order  The rule's order in relation to other rules. A rule with a lower order than another rule executes
     *                       first. If no order is provided it will automatically be one greater than the current maximum
     * @param bool   $enable true if the rule is enabled, false otherwise
     *
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @return object|object[]
     * @see https://auth0.com/docs/api/management/v2#!/Rules/post_rules
     */
    public function create(string $name, string $script, int $order = 0, bool $enable = false)
    {
        $body = [
            'name' => $name,
            'script' => $script,
            'enable' => $enable,
        ];

        $this->addIntegerProperty($body, 'order', $order);

        $response = $this->client
            ->request(Client::METHOD_GET)
            ->addPath('rules')
            ->withHeader(new ContentType('application/json'))
            ->withBody(\GuzzleHttp\json_encode($body))
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * Retrieves a rule by its ID. Accepts a list of fields to include or exclude in the result.
     * Required scope: "read:rules"
     *
     * @param string $id            The id of the rule to retrieve
     * @param string $fields        A comma separated list of fields to include or exclude (depending on include_fields) from the
     *                              result, empty to retrieve all fields
     * @param bool   $includeFields true if the fields specified are to be included in the result, false otherwise
     *                              (defaults to true)
     *
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @return object|object[]
     * @see https://auth0.com/docs/api/management/v2#!/Rules/get_rules_by_id
     */
    public function get(string $id, string $fields = '', bool $includeFields = true)
    {
        $body = [
            'include_fields' => $includeFields,
        ];

        $this->addStringProperty($body, 'fields', $fields);

        $response = $this->client
            ->request(Client::METHOD_GET)
            ->addPath('rules')
            ->addPath($id)
            ->withHeader(new ContentType('application/json'))
            ->withBody(\GuzzleHttp\json_encode($body))
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * To be used to delete a rule.
     * Required scope: "delete:rules"
     *
     * @param string $id  The id of the rule to delete
     *
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @return object|object[]
     * @see https://auth0.com/docs/api/management/v2#!/Rules/delete_rules_by_id
     */
    public function delete(string $id)
    {
        $response = $this->client
            ->request(Client::METHOD_DELETE)
            ->addPath('rules')
            ->addPath($id)
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * Use this endpoint to update an existing rule.
     * Required scope: "update:rules"
     *
     * @param string $id      The id of the rule to retrieve
     * @param string $script  A script that contains the rule's code
     * @param string $name    The name of the rule. Can only contain alphanumeric characters, spaces and '-'. Can neither start
     *                        nor end with '-' or spaces
     * @param int    $order   The rule's order in relation to other rules. A rule with a lower order than another rule executes
     *                        first. Send null to delete
     * @param bool   $enabled true if the rule is enabled, false otherwise
     *
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @return object|object[]
     * @see https://auth0.com/docs/api/management/v2#!/Rules/patch_rules_by_id
     */
    public function update(string $id, string $script = '', string $name = '', int $order = 0, bool $enabled = true)
    {
        $body = [
            'enabled' => $enabled,
        ];

        $this->addStringProperty($body, 'script', $script);
        $this->addStringProperty($body, 'name', $name);
        $this->addIntegerProperty($body, 'order', $order);

        $response = $this->client
            ->request(Client::METHOD_GET)
            ->addPath('rules')
            ->addPath($id)
            ->withHeader(new ContentType('application/json'))
            ->withBody(\GuzzleHttp\json_encode($body))
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }
}
