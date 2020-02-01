<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Api\Management;

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

use Auth0\SDK\API\Header\ContentType;
use Auth0\SDK\Exception\ApiException;
use Auth0\SDK\Exception\CoreException;
use Bitmotion\Auth0\Domain\Model\Auth0\Api\Client;
use Symfony\Component\VarExporter\Exception\ClassNotFoundException;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class JobApi extends GeneralManagementApi
{
    /**
     * Retrieves a job. Useful to check its status.
     * Required scopes: "create:users read:users create:passwords_checking_job"
     *
     * @param  string $id  The id of the job
     *
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @return object|ObjectStorage
     * @see https://auth0.com/docs/api/management/v2#!/Jobs/get_jobs_by_id
     */
    public function get(string $id)
    {
        $response = $this->client
            ->request(Client::METHOD_GET)
            ->addPath('jobs')
            ->addPath($id)
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * Use this endpoint to retrieve error details based on the id of the job. See below for possible responses.
     * Required scopes: "create:users read:users create:passwords_checking_job"
     *
     * @param  string $id  The id of the job
     *
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @return object|ObjectStorage
     * @see https://auth0.com/docs/api/management/v2#!/Jobs/get_errors
     */
    public function getErrors(string $id)
    {
        $response = $this->client
            ->request(Client::METHOD_GET)
            ->addPath('jobs')
            ->addPath($id)
            ->addPath('errors')
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * Gets results of a job
     * Required scopes: "create:passwords_checking_job"
     *
     * @param  string $id  The id of the job
     *
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @return object|ObjectStorage
     * @see https://auth0.com/docs/api/management/v2#!/Jobs/get_results
     */
    public function getResults(string $id)
    {
        $response = $this->client
            ->request(Client::METHOD_GET)
            ->addPath('jobs')
            ->addPath($id)
            ->addPath('results')
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * Export all users to a file using a long running job.
     * Required scope: "read:users"
     *
     * @param string $connection The connection id of the connection from which users will be exported
     * @param string $format     The format of the file. Valid values are: "json" and "csv".
     * @param int    $limit      Limit the number of records.
     * @param array  $fields     A list of fields to be included in the CSV. If omitted, a set of predefined fields will be
     *                           exported.
     *
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @return object|ObjectStorage
     * @see https://auth0.com/docs/api/management/v2#!/Jobs/post_users_exports
     */
    public function exportUsers(string $connection = '', string $format = 'json', int $limit = 0, array $fields = [])
    {
        $body = [
            'format' => $format,
        ];

        $this->addStringProperty($body, 'connection', $connection);
        $this->addArrayProperty($body, 'fields', $fields);
        $this->addIntegerProperty($body, 'limit', $limit);

        $response = $this->client
            ->request(Client::METHOD_POST)
            ->addPath('jobs')
            ->addPath('users-exports')
            ->withHeader(new ContentType('application/json'))
            ->withBody(\GuzzleHttp\json_encode($body))
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * Imports users to a connection from a file using a long running job. Important: The documentation for the file format
     * is https://docs.auth0.com/bulk-import.
     * Required scope: "create:users"
     *
     * @param string $file                A file containing the users to import
     * @param string $connection          The connection id of the connection to which users will be inserted
     * @param bool   $upsert              Update the user if already exists
     * @param string $externalId          Customer defined id
     * @param bool   $sendCompletionEmail If true, send the completion email to all tenant owners when the job is finished
     *
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @return object|ObjectStorage
     * @see https://auth0.com/docs/api/management/v2#!/Jobs/post_users_imports
     */
    public function importUsers(
        string $file,
        string $connection,
        bool $upsert = true,
        string $externalId = '',
        bool $sendCompletionEmail = false
    ) {
        $request = $this->client
            ->request(Client::METHOD_POST)
            ->addPath('jobs')
            ->addPath('users-imports')
            ->addFile('users', $file)
            ->addFormParam('connection_id', $connection)
            ->addFormParam('upsert', $upsert)
            ->addFormParam('send_completion_email', $sendCompletionEmail);

        if ($externalId !== '') {
            $request->addFormParam('external_id', $externalId);
        }

        $response = $request->setReturnType('object')->call();

        return $this->mapResponse($response);
    }

    /**
     * Send an email to the specified user that asks them to click a link to verify their email address.
     * Please note that you must have the `Status` toggle enabled for the verification email template for the email to be sent.
     * Required scope: "update:users"
     *
     * @param string $user   The user_id of the user to whom the email will be sent
     * @param string $client The id of the client, if not provided the global one will be used
     *
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @return object|ObjectStorage
     * @see https://auth0.com/docs/api/management/v2#!/Jobs/post_verification_email
     */
    public function sendVerificationEmail(string $user, string $client = '')
    {
        $body = [
            'user_id' => $user,
        ];

        $this->addStringProperty($body, 'client_id', $client);

        $response = $this->client
            ->request(Client::METHOD_POST)
            ->addPath('jobs')
            ->addPath('verification-email')
            ->withHeader(new ContentType('application/json'))
            ->withBody(\GuzzleHttp\json_encode($body))
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }
}
