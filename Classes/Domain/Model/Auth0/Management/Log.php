<?php
declare(strict_types = 1);
namespace Bitmotion\Auth0\Domain\Model\Auth0\Management;

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

class Log
{
    /**
     * Success Login
     */
    const TYPE_S = 's';

    /**
     * Success Silent Auth
     */
    const TYPE_SSA = 'ssa';

    /**
     * Failed Silent Auth
     */
    const TYPE_FSA = 'fsa';

    /**
     * Success Exchange (Authorization Code for Access Token)
     */
    const TYPE_SEACFT = 'seacft';

    /**
     * Failed Exchange (Authorization Code for Access Token)
     */
    const TYPE_FEACFT = 'feacft';

    /**
     * Success Exchange (Client Credentials for Access Token)
     */
    const TYPE_SECCFT = 'seccft';

    /**
     * Failed Exchange (Client Credentials for Access Token)
     */
    const TYPE_FECCFT = 'feccft';

    /**
     * Success Exchange (Password for Access Token)
     */
    const TYPE_SEPFT = 'sepft';

    /**
     * Failed Exchange (Password for Access Token)
     */
    const TYPE_FEPTF = 'fepft';

    /**
     * Failed Login
     */
    const TYPE_F = 'f';

    /**
     * Warnings During Login
     */
    const TYPE_W = 'w';

    /**
     * Deleted User
     */
    const TYPE_DU = 'du';

    /**
     * Failed Login (invalid email/username)
     */
    const TYPE_FU = 'fu';

    /**
     * Failed Login (wrong password)
     */
    const TYPE_FP = 'fp';

    /**
     * Failed by Connector
     */
    const TYPE_FC = 'fc';

    /**
     * Failed by CORS
     */
    const TYPE_FCO = 'fco';

    /**
     * Connector Online
     */
    const TYPE_CON = 'con';

    /**
     * Connector Offline
     */
    const TYPE_COFF = 'coff';

    /**
     * Failed Connector Provisioning
     */
    const TYPE_FCPRO = 'fcpro';

    /**
     * Success Signup
     */
    const TYPE_SS = 'ss';

    /**
     * Failed Signup
     */
    const TYPE_FS = 'fs';

    /**
     * Code Sent
     */
    const TYPE_CS = 'cs';

    /**
     * Code/Link Sent
     */
    const TYPE_CLS = 'cls';

    /**
     * Success Verification Email
     */
    const TYPE_SV = 'sv';

    /**
     * Failed Verification Email
     */
    const TYPE_FV = 'fv';

    /**
     * Success Change Password
     */
    const TYPE_SCP = 'scp';

    /**
     * Failed Change Password
     */
    const TYPE_FCP = 'fcp';

    /**
     * Success Change Email
     */
    const TYPE_SCE = 'sce';

    /**
     * Failed Change Email
     */
    const TYPE_FCE = 'fce';

    /**
     * Success Change Username
     */
    const TYPE_SCU = 'scu';

    /**
     * Failed Change Username
     */
    const TYPE_FCU = 'fcu';

    /**
     * Success Change Phone Number
     */
    const TYPE_SCPN = 'scpn';

    /**
     * Failed Change Phone Number
     */
    const TYPE_FCPN = 'fcpn';

    /**
     * Success Verification Email Request
     */
    const TYPE_SVR = 'svr';

    /**
     * Failed Verification Email Request
     */
    const TYPE_FVR = 'fvr';

    /**
     * Success Change Password Request
     */
    const TYPE_SCPR = 'scpr';

    /**
     * Failed Change Password Request
     */
    const TYPE_FCPR = 'fcpr';

    /**
     * Failed Sending Notification
     */
    const TYPE_FN = 'fn';

    /**
     * API Operation
     */
    const TYPE_SAPI = 'sapi';

    /**
     * Failed API Operation
     */
    const TYPE_FAPI = 'fapi';

    /**
     * Blocked Account
     */
    const TYPE_LIMIT_WC = 'limit_wc';

    /**
     * Blocked IP Address
     */
    const TYPE_LIMIT_MU = 'limit_mu';

    /**
     * Too Many Calls to /userinfo
     */
    const TYPE_LIMIT_UI = 'limit_ui';

    /**
     * Rate Limit On API
     */
    const TYPE_API_LIMIT = 'api_limit';

    /**
     * Successful User Deletion
     */
    const TYPE_SDU = 'sdu';

    /**
     * Failed User Deletion
     */
    const TYPE_FDU = 'fdu';

    /**
     * Success Logout
     */
    const TYPE_SLO = 'slo';

    /**
     * Failed Logout
     */
    const TYPE_FLO = 'flo';

    /**
     * Success Delegation
     */
    const TYPE_SD = 'sd';

    /**
     * Failed Delegation
     */
    const TYPE_FD = 'fd';

    /**
     * Failed Cross Origin Authentication
     */
    const TYPE_FCOA = 'fcoa';

    /**
     * Success Cross Origin Authentication
     */
    const TYPE_SCOA = 'scoa';

    /**
     * The date when the event was created
     *
     * @var \DateTime
     */
    protected $date;

    /**
     * The log event type
     *
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $description;

    /**
     * The id of the client
     *
     * @var string
     */
    protected $clientId;

    /**
     * The name of the client
     *
     * @var string
     */
    protected $clientName;

    /**
     * The IP of the log event source
     *
     * @var string
     */
    protected $ip;

    /**
     * @var string
     */
    protected $userAgent;

    /**
     * Used to store additional metadata
     *
     * @var array
     */
    protected $locationInfo;

    /**
     * Used to store additional metadata
     *
     * @var array
     */
    protected $details;

    /**
     * The user's unique identifier
     *
     * @var string
     */
    protected $userId;

    /**
     * @var array
     */
    protected $auth0Client;

    /**
     * @var string
     */
    protected $logId;

    /**
     * @var bool
     */
    protected $isMobile;

    /**
     * @return string
     */
    public function getDate()
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): void
    {
        $this->date = $date;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    public function setClientId(string $clientId): void
    {
        $this->clientId = $clientId;
    }

    /**
     * @return string
     */
    public function getClientName()
    {
        return $this->clientName;
    }

    public function setClientName(string $clientName): void
    {
        $this->clientName = $clientName;
    }

    /**
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    public function setIp(string $ip): void
    {
        $this->ip = $ip;
    }

    /**
     * @return array
     */
    public function getLocationInfo()
    {
        return $this->locationInfo;
    }

    public function setLocationInfo(array $locationInfo): void
    {
        $this->locationInfo = $locationInfo;
    }

    /**
     * @return array
     */
    public function getDetails()
    {
        return $this->details;
    }

    public function setDetails(array $details): void
    {
        $this->details = $details;
    }

    /**
     * @return string
     */
    public function getUserId()
    {
        return $this->userId;
    }

    public function setUserId(string $userId): void
    {
        $this->userId = $userId;
    }

    /**
     * @return string
     */
    public function getLogId()
    {
        return $this->logId;
    }

    public function setLogId(string $logId): void
    {
        $this->logId = $logId;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getUserAgent()
    {
        return $this->userAgent;
    }

    public function setUserAgent(string $userAgent): void
    {
        $this->userAgent = $userAgent;
    }

    /**
     * @return array
     */
    public function getAuth0Client()
    {
        return $this->auth0Client;
    }

    public function setAuth0Client(array $auth0Client): void
    {
        $this->auth0Client = $auth0Client;
    }

    /**
     * @return bool
     */
    public function isMobile()
    {
        return $this->isMobile;
    }

    public function setIsMobile(bool $isMobile): void
    {
        $this->isMobile = $isMobile;
    }
}
