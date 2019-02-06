<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Api\Management;

use Auth0\SDK\API\Header\ContentType;
use Auth0\SDK\Exception\ApiException;
use Auth0\SDK\Exception\CoreException;
use Symfony\Component\VarExporter\Exception\ClassNotFoundException;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class EmailTemplateApi extends GeneralManagementApi
{
    /**
     * Get an email template
     * Required scope: "read:email_templates"
     *
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @return object|ObjectStorage
     * @see https://auth0.com/docs/api/management/v2#!/Email_Templates/get_email_templates_by_templateName
     */
    public function get(string $name)
    {
        $response = $this->apiClient
            ->method('get')
            ->addPath('email-templates')
            ->addPath($name)
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * Patch an email template
     * Required scope: "update:email_templates"
     *
     * @param string $name              The template name (verify_email, reset_email, welcome_email, blocked_account,
     *                                  stolen_credentials, enrollment_email, mfa_oob_code).
     * @param string $emailBody         The body of the template.
     * @param string $from              The sender of the email.
     * @param string $subject           The subject of the email.
     * @param string $syntax            The syntax of the template body.
     * @param string $resultUri         The URL to redirect the user to after a successful action.
     * @param int    $lifetime          The lifetime in seconds that the link within the email will be valid for.
     * @param bool   $includeInRedirect Whether or not we include the email as part of the returnUrl in the reset_email
     * @param bool   $enabled           Whether or not the template is enabled.
     *
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @return object|ObjectStorage
     * @see https://auth0.com/docs/api/management/v2#!/Email_Templates/patch_email_templates_by_templateName
     */
    public function patch(
        string $name,
        string $emailBody = '',
        string $from = '',
        string $subject = '',
        string $syntax = '',
        string $resultUri = '',
        int $lifetime = 0,
        bool $includeInRedirect = false,
        bool $enabled = true
    ) {
        $body = [
            'template' => $name,
            'includeEmailInRedirect' => $includeInRedirect,
            'enabled' => $enabled,
        ];

        $this->addStringProperty($body, 'body', $emailBody);
        $this->addStringProperty($body, 'from', $from);
        $this->addStringProperty($body, 'subject', $subject);
        $this->addStringProperty($body, 'syntax', $syntax);
        $this->addStringProperty($body, 'resultUrl', $resultUri);
        $this->addIntegerProperty($body, 'urlLifetimeInSeconds', $lifetime);

        $response = $this->apiClient
            ->method('patch')
            ->addPath('email-templates')
            ->addPath($name)
            ->withHeader(new ContentType('application/json'))
            ->withBody(\GuzzleHttp\json_encode($body))
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * Update an email template
     * Required scope: "update:email_templates"
     *
     * @param string $name              The template name (verify_email, reset_email, welcome_email, blocked_account,
     *                                  stolen_credentials, enrollment_email, mfa_oob_code).
     * @param string $emailBody         The body of the template.
     * @param string $from              The sender of the email.
     * @param string $subject           The subject of the email.
     * @param string $syntax            The syntax of the template body.
     * @param string $resultUri         The URL to redirect the user to after a successful action.
     * @param int    $lifetime          The lifetime in seconds that the link within the email will be valid for.
     * @param bool   $includeInRedirect Whether or not we include the email as part of the returnUrl in the reset_email
     * @param bool   $enabled           Whether or not the template is enabled.
     *
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @return object|ObjectStorage
     * @see https://auth0.com/docs/api/management/v2#!/Email_Templates/put_email_templates_by_templateName
     */
    public function update(
        string $name,
        string $emailBody,
        string $from,
        string $subject,
        string $syntax,
        string $resultUri = '',
        int $lifetime = 0,
        bool $includeInRedirect = false,
        bool $enabled = true
    ) {
        $body = [
            'template' => $name,
            'body' => $emailBody,
            'from' => $from,
            'subject' => $subject,
            'syntax' => $syntax,
            'includeEmailInRedirect' => $includeInRedirect,
            'enabled' => $enabled,
        ];

        $this->addStringProperty($body, 'resultUrl', $resultUri);
        $this->addIntegerProperty($body, 'urlLifetimeInSeconds', $lifetime);

        $response = $this->apiClient
            ->method('put')
            ->addPath('email-templates')
            ->addPath($name)
            ->withHeader(new ContentType('application/json'))
            ->withBody(\GuzzleHttp\json_encode($body))
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * Create an email template
     * Required scope: "create:email_templates"
     *
     * @param string $name              The template name (verify_email, reset_email, welcome_email, blocked_account,
     *                                  stolen_credentials, enrollment_email, mfa_oob_code).
     * @param string $emailBody         The body of the template.
     * @param string $from              The sender of the email.
     * @param string $subject           The subject of the email.
     * @param string $syntax            The syntax of the template body.
     * @param string $resultUri         The URL to redirect the user to after a successful action.
     * @param int    $lifetime          The lifetime in seconds that the link within the email will be valid for.
     * @param bool   $includeInRedirect Whether or not we include the email as part of the returnUrl in the reset_email
     * @param bool   $enabled           Whether or not the template is enabled.
     *
     * @throws ApiException
     * @throws ClassNotFoundException
     * @throws CoreException
     * @return object|ObjectStorage
     * @see https://auth0.com/docs/api/management/v2#!/Email_Templates/post_email_templates
     */
    public function create(
        string $name,
        string $emailBody,
        string $from,
        string $subject,
        string $syntax,
        string $resultUri = '',
        int $lifetime = 0,
        bool $includeInRedirect = false,
        bool $enabled = true
    ) {
        $body = [
            'template' => $name,
            'body' => $emailBody,
            'from' => $from,
            'subject' => $subject,
            'syntax' => $syntax,
            'includeEmailInRedirect' => $includeInRedirect,
            'enabled' => $enabled,
        ];

        $this->addStringProperty($body, 'resultUrl', $resultUri);
        $this->addIntegerProperty($body, 'urlLifetimeInSeconds', $lifetime);

        $response = $this->apiClient
            ->method('post')
            ->addPath('email-templates')
            ->withHeader(new ContentType('application/json'))
            ->withBody(\GuzzleHttp\json_encode($body))
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }
}
