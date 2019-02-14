<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Api\Management;

use Auth0\SDK\API\Header\ContentType;
use Auth0\SDK\Exception\ApiException;
use Auth0\SDK\Exception\CoreException;
use Bitmotion\Auth0\Domain\Model\Auth0\Management\Ticket;
use Bitmotion\Auth0\Domain\Model\Auth0\Management\User;
use TYPO3\CMS\Extbase\Object\Exception;

class TicketApi extends GeneralManagementApi
{
    /**
     * This endpoint can be used to create a ticket to verify a user's email.
     * Required scope: "create:user_tickets"
     *
     * @param User   $user          The user_id of for which the ticket is to be created
     * @param string $resultUri     The user will be redirected to this endpoint once the ticket is used
     * @param int    $ttl           The ticket's lifetime in seconds starting from the moment of creation. After expiration, the
     *                              ticket cannot be used to verify the user's email. If not specified or if you send 0, the Auth0
     *                              default lifetime of five days will be applied
     *
     * @throws ApiException
     * @throws Exception
     * @throws CoreException
     * @return Ticket
     * @see https://auth0.com/docs/api/management/v2#!/Tickets/post_email_verification
     */
    public function createEmailVerificationTicket(User $user, string $resultUri = '', int $ttl = 0)
    {
        $body = [
            'user_id' => $user->getUserId(),
        ];

        $this->addStringProperty($body, 'result_url', $resultUri);
        $this->addIntegerProperty($body, 'ttl_sec', $ttl);

        $response = $this->client
            ->request('post')
            ->addPath('tickets')
            ->addPath('email-verification')
            ->withHeader(new ContentType('application/json'))
            ->withBody(\GuzzleHttp\json_encode($body))
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * This endpoint can be used to create a password change ticket for a user.
     * Required scope: "create:user_tickets"
     *
     * @param User   $user                    The user_id of for which the ticket is to be created
     * @param string $resultUri                 The user will be redirected to this endpoint once the ticket is used
     * @param int    $ttl                       The ticket's lifetime in seconds starting from the moment of creation. After
     *                                          expiration, the ticket cannot be used to change the user's password. If not
     *                                          specified or if you send 0, the Auth0 default lifetime of 5 days will be applied
     * @param bool   $markEmailAsVerified       true if email_verified attribute must be set to true once password is changed,
     *                                          false if email_verified attribute should not be updated
     * @param bool   $includeEmailInRedirect    Whether or not we include the email as part of the returnUrl in the reset_email
     *
     * @throws ApiException
     * @throws Exception
     * @throws CoreException
     * @return Ticket
     * @see https://auth0.com/docs/api/management/v2#!/Tickets/post_password_change
     */
    public function createPasswordChangeTicket(
        User $user,
        string $resultUri = '',
        int $ttl = 0,
        bool $markEmailAsVerified = false,
        bool $includeEmailInRedirect = false
    ) {
        return $this->createPasswordChangeTicketRaw(
            $user->getUserId(),
            '',
            '',
            $resultUri,
            $ttl,
            $markEmailAsVerified,
            $includeEmailInRedirect
        );
    }

    /**
     * This endpoint can be used to create a password change ticket for a user.
     * Required scope: "create:user_tickets"
     *
     * @param string $email                     The user's email
     * @param string $connection                The connection that provides the identity for which the password is to be changed.
     *                                          If sending this parameter, the email is also required and the user_id is invalid
     * @param string $resultUri                 The user will be redirected to this endpoint once the ticket is used
     * @param int    $ttl                       The ticket's lifetime in seconds starting from the moment of creation. After
     *                                          expiration, the ticket cannot be used to change the user's password. If not
     *                                          specified or if you send 0, the Auth0 default lifetime of 5 days will be applied
     * @param bool   $markEmailAsVerified       true if email_verified attribute must be set to true once password is changed,
     *                                          false if email_verified attribute should not be updated
     * @param bool   $includeEmailInRedirect    Whether or not we include the email as part of the returnUrl in the reset_email
     *
     * @throws ApiException
     * @throws Exception
     * @throws CoreException
     * @return Ticket
     * @see https://auth0.com/docs/api/management/v2#!/Tickets/post_password_change
     */
    public function createPasswordChangeTicketByEmail(
        string $email,
        string $connection,
        string $resultUri = '',
        int $ttl = 0,
        bool $markEmailAsVerified = false,
        bool $includeEmailInRedirect = false
    ) {
        return $this->createPasswordChangeTicketRaw(
            '',
            $email,
            $connection,
            $resultUri,
            $ttl,
            $markEmailAsVerified,
            $includeEmailInRedirect
        );
    }

    /**
     * This endpoint can be used to create a password change ticket for a user.
     * Required scope: "create:user_tickets"
     *
     * @param string $user                      The user_id of for which the ticket is to be created
     * @param string $email                     The user's email
     * @param string $connection                The connection that provides the identity for which the password is to be changed.
     *                                          If sending this parameter, the email is also required and the user_id is invalid
     * @param string $resultUri                 The user will be redirected to this endpoint once the ticket is used
     * @param int    $ttl                       The ticket's lifetime in seconds starting from the moment of creation. After
     *                                          expiration, the ticket cannot be used to change the user's password. If not
     *                                          specified or if you send 0, the Auth0 default lifetime of 5 days will be applied
     * @param bool   $markEmailAsVerified       true if email_verified attribute must be set to true once password is changed,
     *                                          false if email_verified attribute should not be updated
     * @param bool   $includeEmailInRedirect    Whether or not we include the email as part of the returnUrl in the reset_email
     *
     * @throws ApiException
     * @throws Exception
     * @throws CoreException
     * @return Ticket
     * @see https://auth0.com/docs/api/management/v2#!/Tickets/post_password_change
     */
    protected function createPasswordChangeTicketRaw(
        string $user = '',
        string $email = '',
        string $connection = '',
        string $resultUri = '',
        int $ttl = 0,
        bool $markEmailAsVerified = false,
        bool $includeEmailInRedirect = false
    ) {
        $body = [
            'mark_email_as_verified' => $markEmailAsVerified,
            'includeEmailInRedirect' => $includeEmailInRedirect,
        ];

        $this->addStringProperty($body, 'connection_id', $connection);
        $this->addStringProperty($body, 'user_id', $user);
        $this->addStringProperty($body, 'email', $email);
        $this->addStringProperty($body, 'result_url', $resultUri);
        $this->addIntegerProperty($body, 'ttl_sec', $ttl);

        $response = $this->client
            ->request('post')
            ->addPath('tickets')
            ->addPath('password-change')
            ->withHeader(new ContentType('application/json'))
            ->withBody(\GuzzleHttp\json_encode($body))
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }
}
