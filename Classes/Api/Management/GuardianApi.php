<?php
declare(strict_types = 1);
namespace Bitmotion\Auth0\Api\Management;

/***
 *
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2018 Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 *
 ***/

use Auth0\SDK\API\Header\ContentType;
use Auth0\SDK\Exception\ApiException;
use Auth0\SDK\Exception\CoreException;
use Bitmotion\Auth0\Domain\Model\Auth0\Api\Client;
use Symfony\Component\VarExporter\Exception\ClassNotFoundException;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class GuardianApi extends GeneralManagementApi
{
    /**
     * Retrieves all factors. Useful to check factor enablement and trial status.
     * Required scope: "read:guardian_factors"
     *
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @return object|ObjectStorage
     * @see https://auth0.com/docs/api/management/v2#!/Guardian/get_factors
     */
    public function getFactors()
    {
        $response = $this->client
            ->request(Client::METHOD_GET)
            ->addPath('guardian')
            ->addPath('factor')
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * Retrieves an enrollment. Useful to check its type and related metadata. Note that phone number data is partially obfuscated.
     * Required scope: "read:guardian_enrollments"
     *
     * @param string $id The id of the enrollment that is going to be updated
     *
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @return object|ObjectStorage
     * @see https://auth0.com/docs/api/management/v2#!/Guardian/get_enrollments_by_id
     */
    public function getEnrollments(string $id)
    {
        $response = $this->client
            ->request(Client::METHOD_GET)
            ->addPath('guardian')
            ->addPath('enrollments')
            ->addPath($id)
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * Deletes an enrollment. Useful when you want to force the user to re-enroll with Guardian.
     * Required scope: "delete:guardian_enrollments"
     *
     * @param string $id The id of the enrollment that is going to be updated
     *
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @return object|ObjectStorage
     * @see https://auth0.com/docs/api/management/v2#!/Guardian/delete_enrollments_by_id
     */
    public function deleteEnrollments(string $id)
    {
        $response = $this->client
            ->request(Client::METHOD_DELETE)
            ->addPath('guardian')
            ->addPath('enrollments')
            ->addPath($id)
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * Retrieves enrollment and verification templates. You can use this to check the current values for your templates.
     * Required scope: "read:guardian_factors"
     *
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @return object|ObjectStorage
     * @see https://auth0.com/docs/api/management/v2#!/Guardian/get_templates
     */
    public function getTemplates()
    {
        $response = $this->client
            ->request(Client::METHOD_GET)
            ->addPath('guardian')
            ->addPath('factors')
            ->addPath('sms')
            ->addPath('templates')
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * Useful to send custom messages on SMS enrollment and verification.
     * Required scope: "update:guardian_factors"
     *
     * @param string $enrollmentMessage   Message sent to the user when they are invited to enroll with a phone number
     * @param string $verificationMessage Message sent to the user when they are prompted to verify their account
     *
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @return object|ObjectStorage
     * @see https://auth0.com/docs/api/management/v2#!/Guardian/put_templates
     */
    public function updateTemplates(string $enrollmentMessage, string $verificationMessage)
    {
        $body = [
            'enrollment_message' => $enrollmentMessage,
            'verification_message' => $verificationMessage,
        ];

        $response = $this->client
            ->request(Client::METHOD_PUT)
            ->addPath('guardian')
            ->addPath('factors')
            ->addPath('sms')
            ->addPath('templates')
            ->withHeader(new ContentType('application/json'))
            ->withBody(\GuzzleHttp\json_encode($body))
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * Returns provider configuration for AWS SNS.
     * Required scope: "read:guardian_factors"
     *
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @return object|ObjectStorage
     * @see https://auth0.com/docs/api/management/v2#!/Guardian/get_sns
     */
    public function getSns()
    {
        $response = $this->client
            ->request(Client::METHOD_GET)
            ->addPath('guardian')
            ->addPath('factors')
            ->addPath('push-notification')
            ->addPath('providers')
            ->addPath('sns')
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * Returns provider configuration.
     * Required scope: "read:guardian_factors"
     *
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @return object|ObjectStorage
     * @see https://auth0.com/docs/api/management/v2#!/Guardian/get_twilio
     */
    public function getTwilio()
    {
        $response = $this->client
            ->request(Client::METHOD_GET)
            ->addPath('guardian')
            ->addPath('factors')
            ->addPath('sms')
            ->addPath('providers')
            ->addPath('twilio')
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * Useful to configure SMS provider.
     * Required scope: "update:guardian_factors"
     *
     * @param string $from       From number
     * @param string $copilotSid Copilot SID
     * @param string $authToken  Twilio Authentication token
     * @param string $sid        Twilio SID
     *
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @return object|ObjectStorage
     * @see https://auth0.com/docs/api/management/v2#!/Guardian/put_twilio
     */
    public function updateTwilio(string $from = '', string $copilotSid = '', string $authToken = '', string $sid = '')
    {
        $body = [];

        $this->addStringProperty($body, 'from', $from);
        $this->addStringProperty($body, 'messaging_service_sid', $copilotSid);
        $this->addStringProperty($body, 'auth_token', $authToken);
        $this->addStringProperty($body, 'sid', $sid);

        $response = $this->client
            ->request(Client::METHOD_PUT)
            ->addPath('guardian')
            ->addPath('factors')
            ->addPath('sms')
            ->addPath('providers')
            ->addPath('twilio')
            ->withHeader(new ContentType('application/json'))
            ->withBody(\GuzzleHttp\json_encode($body))
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * Generate an email with a link to start the Guardian enrollment process.
     * Required scope: "create:guardian_enrollment_tickets"
     *
     * @param string $user     user_id for the enrollment ticket
     * @param string $email    alternate email to which the enrollment email will be sent. Optional - by default, the email will
     *                         be sent to the user's default address
     * @param bool   $sendMail Send an email to the user to start the enrollment
     *
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @return object|ObjectStorage
     * @see https://auth0.com/docs/api/management/v2#!/Guardian/post_ticket
     */
    public function createTicket(string $user, string $email = '', bool $sendMail = false)
    {
        $body = [
            'user_id' => $user,
            'send_mail' => $sendMail,
        ];

        $this->addStringProperty($body, 'email', $email);

        $response = $this->client
            ->request(Client::METHOD_POST)
            ->addPath('guardian')
            ->addPath('enrollments')
            ->addPath('ticket')
            ->withHeader(new ContentType('application/json'))
            ->withBody(\GuzzleHttp\json_encode($body))
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * Useful to enable / disable factor.
     * Required scope: "update:guardian_factors"
     *
     * @param string $name    Factor name: push-notification, sms, email, duo or otp
     * @param bool   $enabled States if this factor is enabled
     *
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @return object|ObjectStorage
     * @see https://auth0.com/docs/api/management/v2#!/Guardian/put_factors_by_name
     */
    public function updateFactor(string $name, bool $enabled = false)
    {
        $response = $this->client
            ->request(Client::METHOD_PUT)
            ->addPath('guardian')
            ->addPath('factors')
            ->addPath($name)
            ->withHeader(new ContentType('application/json'))
            ->withBody(\GuzzleHttp\json_encode(['enabled' => $enabled]))
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }
}
