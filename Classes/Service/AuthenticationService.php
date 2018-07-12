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
use Bitmotion\Auth0\Domain\Model\Application;
use Bitmotion\Auth0\Domain\Model\Dto\EmAuth0Configuration;
use Bitmotion\Auth0\Domain\Repository\ApplicationRepository;
use Bitmotion\Auth0\Utility\UpdateUtility;
use Bitmotion\Auth0\Utility\UserUtility;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * Class AuthenticationService
 * @package Bitmotion\Auth0\Service
 */
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
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    public function initAuth($mode, $loginData, $authInfo, $pObj)
    {
        if ($this->initializeAuth0Connections()) {

            $authInfo['db_user']['check_pid_clause'] = false;
            $this->db_user = $authInfo['db_user'];
            $this->db_groups = $authInfo['db_groups'];
            $this->mode = $mode;
            $this->login = $loginData;
            $this->authInfo = $authInfo;
            $this->pObj = $pObj;

            if ($mode === 'getUserFE' && !empty($loginData)) {
                // Handle FE Login
                $this->tableName = 'fe_users';
                $this->user = UserUtility::checkIfUserExists($this->tableName, $this->tokenInfo['sub']);
                if (!$this->user) {
                    UserUtility::insertFeUser($this->tableName, $this->auth0User);
                }
            } elseif ($mode === 'getUserBE' && !empty($loginData)) {
                // Handle BE Login
                $this->tableName = 'be_users';
                $this->user = UserUtility::checkIfUserExists($this->tableName, $this->tokenInfo['sub']);
                $updateUtility = GeneralUtility::makeInstance(UpdateUtility::class, $this->tableName, $this->auth0User);
                if (!$this->user) {
                    // Insert new BE User
                    UserUtility::insertBeUser($this->tableName, $this->auth0User);
                    $updateUtility->updateUser();
                } elseif (strtotime($this->auth0User['updated_at']) > $this->user['tstamp']) {
                    // Update existing user
                    $updateUtility->updateUser();
                }
            }
        }
    }

    /**
     * @return bool
     * @throws \Auth0\SDK\Exception\ApiException
     * @throws \Auth0\SDK\Exception\CoreException
     * @throws \Exception
     */
    protected function initializeAuth0Connections():bool
    {
        if (TYPO3_MODE === 'BE' && GeneralUtility::_GP('loginProvider') != '1526966635') {
            // Not an Auth0 login
            return false;
        }

        if (TYPO3_MODE === 'FE') {
            $GLOBALS['TSFE']->sys_page = GeneralUtility::makeInstance(PageRepository::class);
            $GLOBALS['TSFE']->sys_page->init(false);
            $applicationUid = GeneralUtility::_GP('application');
        } else {
            $emConfiguration = new EmAuth0Configuration();
            $applicationUid = $emConfiguration->getBackendConnection();
        }


        /** @var Application $application */
        $applicationRepository = GeneralUtility::makeInstance(ObjectManager::class)->get(ApplicationRepository::class);
        $application = $applicationRepository->findByUid($applicationUid);

        if ($application instanceof Application) {
            $authenticationApi = new AuthenticationApi(
                $application,
                GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST') . '/typo3/?loginProvider=1526966635&login=1',
                'read:current_user openid profile'
            );
            $this->tokenInfo = $authenticationApi->getUser();
            $managementApi = GeneralUtility::makeInstance(ManagementApi::class, $application);
            $this->auth0User = $managementApi->getUserById($this->tokenInfo['sub']);

            return true;
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

    /**
     * @param array $user
     *
     * @return int
     */
    public function authUser(array $user): int
    {
        // Login user
        if ($user['auth0_user_id'] !== ''  && $user['auth0_user_id'] == $this->tokenInfo['sub']) {

            // Do not login if email address is not verified
            if ($this->auth0User['email_verified'] === false && ($this->mode === 'getUserBE' || (bool)$this->auth0Data['loginIfMailIsNotVerified'] === false)) {
                return 0;
            }

            return 200;
        }

        // Service is not responsible for login request
        return 100;
    }
}