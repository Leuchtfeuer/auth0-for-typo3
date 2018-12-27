<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Service;

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
use Bitmotion\Auth0\Api\AuthenticationApi;
use Bitmotion\Auth0\Api\ManagementApi;
use Bitmotion\Auth0\Domain\Model\Dto\EmAuth0Configuration;
use Bitmotion\Auth0\Exception\InvalidApplicationException;
use Bitmotion\Auth0\Utility\ApplicationUtility;
use Bitmotion\Auth0\Utility\UpdateUtility;
use Bitmotion\Auth0\Utility\UserUtility;
use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Page\PageRepository;

class AuthenticationService extends \TYPO3\CMS\Sv\AuthenticationService
{
    /**
     * @var \stdClass
     */
    protected $auth0Response;

    /**
     * @var array
     */
    protected $auth0Data = [];

    /**
     * @var array
     */
    protected $user = [];

    /**
     * @var array
     */
    protected $tokenInfo = [];

    /**
     * @var string
     */
    protected $tableName = 'fe_users';

    /**
     * @var array
     */
    protected $auth0User = null;

    /**
     * @param string                                                    $mode
     * @param array                                                     $loginData
     * @param array                                                     $authInfo
     * @param \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication $pObj
     *
     * @throws \Auth0\SDK\Exception\ApiException
     * @throws \Auth0\SDK\Exception\CoreException
     * @throws \TYPO3\CMS\Core\Error\Http\PageNotFoundException
     * @throws \TYPO3\CMS\Core\Error\Http\ServiceUnavailableException
     */
    public function initAuth($mode, $loginData, $authInfo, $pObj)
    {
        if ($this->initializeAuth0Connections()) {
            // Set default values
            $authInfo['db_user']['check_pid_clause'] = false;
            $this->db_user = $authInfo['db_user'];
            $this->db_groups = $authInfo['db_groups'];
            $this->mode = $mode;
            $this->login = $loginData;
            $this->authInfo = $authInfo;
            $this->pObj = $pObj;

            // Handle login for frontend or backend
            if ($mode === 'getUserFE' && !empty($loginData)) {
                $this->tableName = 'fe_users';
                $this->insertOrUpdateUser();
            } elseif ($mode === 'getUserBE' && !empty($loginData)) {
                $this->tableName = 'be_users';
                $this->insertOrUpdateUser();
            }
        }
    }

    /**
     * Insert or updates user data in TYPO3 database
     */
    protected function insertOrUpdateUser()
    {
        $userUtility = GeneralUtility::makeInstance(UserUtility::class);
        $this->user = $userUtility->checkIfUserExists($this->tableName, $this->tokenInfo['sub']);

        // Insert a new user into database
        if (empty($this->user)) {
            $userUtility->insertUser($this->tableName, $this->auth0User);
        }

        // Update existing user on every login when we are in BE context
        if (TYPO3_MODE === 'BE') {
            $updateUtility = GeneralUtility::makeInstance(UpdateUtility::class, $this->tableName, $this->auth0User);
            $updateUtility->updateUser();
            $updateUtility->updateGroups();
        }
    }

    /**
     * Initializes the connection to the auth0 server
     *
     *
     * @throws \Auth0\SDK\Exception\ApiException
     * @throws \Auth0\SDK\Exception\CoreException
     * @throws \TYPO3\CMS\Core\Error\Http\PageNotFoundException
     * @throws \TYPO3\CMS\Core\Error\Http\ServiceUnavailableException
     * @throws \Exception
     */
    protected function initializeAuth0Connections(): bool
    {
        if (TYPO3_MODE === 'BE' && GeneralUtility::_GP('loginProvider') != '1526966635') {
            // Not an Auth0 login
            return false;
        }

        if (TYPO3_MODE === 'FE') {
            $applicationUid = (int)GeneralUtility::_GP('application');

            // No application uid found in request - skip.
            if ($applicationUid === 0) {
                return false;
            }
        } else {
            $emConfiguration = new EmAuth0Configuration();
            $applicationUid = $emConfiguration->getBackendConnection();
        }

        try {
            $application = ApplicationUtility::getApplication($applicationUid);
            $authenticationApi = new AuthenticationApi(
                $application,
                // TODO: Use proper redirect uri for FE requests
                GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST') . '/typo3/?loginProvider=1526966635&login=1',
                'read:current_user openid profile'
            );
            $this->tokenInfo = $authenticationApi->getUser();
            $managementApi = GeneralUtility::makeInstance(ManagementApi::class, $application);
            $this->auth0User = $managementApi->getUserById($this->tokenInfo['sub']);

            if (isset($this->auth0User['error'])) {
                return false;
            }

            return true;
        } catch (InvalidApplicationException $exception) {
            // Do nothing
        }

        return false;
    }

    /**
     * @return bool|mixed
     */
    public function getUser()
    {
        if ($this->login['status'] !== 'login') {
            return false;
        }

        $user = $this->fetchUserRecord($this->login['uname'], 'auth0_user_id = "' . $this->tokenInfo['sub'] . '"');

        if (!is_array($user)) {
            // Failed login attempt (no username found)
            $this->writelog(255, 3, 3, 2, 'Login-attempt from %s (%s), username \'%s\' not found!!', [$this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $this->login['uname']]);
            // Logout written to log
            GeneralUtility::sysLog(sprintf('Login-attempt from %s (%s), username \'%s\' not found!', $this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $this->login['uname']), 'core', GeneralUtility::SYSLOG_SEVERITY_WARNING);
        } else {
            if ($this->writeDevLog) {
                GeneralUtility::devLog('User found: ' . GeneralUtility::arrayToLogString($user, [$this->db_user['userid_column'], $this->db_user['username_column']]), self::class);
            }
        }

        return $user;
    }

    public function authUser(array $user): int
    {
        // Login user
        if ($user['auth0_user_id'] !== '' && $user['auth0_user_id'] == $this->tokenInfo['sub']) {
            // Do not login if email address is not verified
            if ($this->auth0User['email_verified'] === false && ($this->mode === 'getUserBE' || (bool)$this->auth0Data['loginIfMailIsNotVerified'] === false)) {
                return 0;
            }

            // Success
            return 200;
        }

        // Service is not responsible for login request
        return 100;
    }
}
