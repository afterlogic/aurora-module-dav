<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\Dav;

/**
 * Integrate SabreDav framework into Aurora platform.
 *
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2023, Afterlogic Corp.
 *
 * @property Settings $oModuleSettings
 *
 * @package Modules
 */
class Module extends \Aurora\System\Module\AbstractModule
{
    public $oManager = null;

    /**
     * @return Module
     */
    public static function getInstance()
    {
        return parent::getInstance();
    }

    /**
     * @return Module
     */
    public static function Decorator()
    {
        return parent::Decorator();
    }

    /**
     * @return Settings
     */
    public function getModuleSettings()
    {
        return $this->oModuleSettings;
    }

    /**
     * @return Manager
     */
    public function getManager()
    {
        if ($this->oManager === null) {
            $this->oManager = new Manager($this);
        }

        return $this->oManager;
    }

    public function GetModuleManager()
    {
        return parent::GetModuleManager();
    }

    /***** private functions *****/
    /**
     * Initializes DAV Module.
     *
     * @ignore
     */
    public function init()
    {
        $this->AddEntry('dav', 'EntryDav');

        $this->subscribeEvent('Calendar::GetCalendars::after', array($this, 'onAfterGetCalendars'));
        $this->subscribeEvent('MobileSync::GetInfo', array($this, 'onGetMobileSyncInfo'));
    }

    /**
     * Writes in $aParameters DAV server URL.
     *
     * @ignore
     * @param array $aArgs
     */
    public function onAfterGetCalendars(&$aArgs, &$mResult)
    {
        if (isset($mResult) && $mResult !== false) {
            $mResult['ServerUrl'] = $this->GetServerUrl();
        }
    }

    /**
     * Writes in $aData information about DAV server.
     *
     * @ignore
     * @param array $mResult
     */
    public function onGetMobileSyncInfo($aArgs, &$mResult)
    {
        $mResult['EnableDav'] = true;
        $mResult['Dav']['Login'] = $this->GetLogin();
        $mResult['Dav']['Server'] = $this->GetServerUrl();
        $mResult['Dav']['PrincipalUrl'] = $this->GetPrincipalUrl();
    }

    /**
     * Creates tables required for module work. Called by event subscribe.
     *
     * @ignore
     * @param array $aArgs Parameters
     * @param mixed $mResult
     */
    public function onAfterCreateTables($aArgs, &$mResult)
    {
        // if ($mResult)
        // {
        // 	$mResult = self::Decorator()->CreateTables();
        // }
    }
    /***** private functions *****/

    /***** public functions *****/

    /**
     * @ignore
     * @return void
     */
    public function EntryDav()
    {
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
        });

        @set_time_limit(3000);

        $sRequestUri = empty($_SERVER['REQUEST_URI']) ? '' : \trim($_SERVER['REQUEST_URI']);

        $oServer = \Afterlogic\DAV\Server::getInstance();
        $oServer->setBaseUri($sRequestUri);

        \Afterlogic\DAV\Server::getInstance()->exec();
    }

    /**
     * Returns DAV client.
     *
     * @return \Aurora\Modules\Dav\Client|false
     */
    public function GetDavClient()
    {
        \Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);

        return $this->getManager()->GetDAVClient(\Aurora\System\Api::getAuthenticatedUserId());
    }

    /**
     * Returns VCARD object.
     *
     * @param string|resource $Data
     * @return \Sabre\VObject\Document
     */
    public function GetVCardObject($Data)
    {
        \Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);

        return $this->getManager()->getVCardObject($Data);
    }
    /***** public functions *****/

    /***** public functions might be called with web API *****/
    /**
     * @apiDefine Dav Dav Module
     * Integrate SabreDav framework into Aurora platform
     */

    /**
     * @api {post} ?/Api/ GetSettings
     * @apiName GetSettings
     * @apiGroup Dav
     * @apiDescription Obtains list of module settings for authenticated user.
     *
     * @apiHeader {string} [Authorization] "Bearer " + Authentication token which was received as the result of Core.Login method.
     * @apiHeaderExample {json} Header-Example:
     *	{
     *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
     *	}
     *
     * @apiParam {string=Dav} Module Module name.
     * @apiParam {string=GetSettings} Method Method name.
     *
     * @apiParamExample {json} Request-Example:
     * {
     *	Module: 'Dav',
     *	Method: 'GetSettings'
     * }
     *
     * @apiSuccess {object[]} Result Array of response objects.
     * @apiSuccess {string} Result.Module Module name.
     * @apiSuccess {string} Result.Method Method name.
     * @apiSuccess {mixed} Result.Result Object in case of success, otherwise **false**.
     * @apiSuccess {string} Result.Result.ExternalHostNameOfDAVServer External host name of DAV server.
     * @apiSuccess {int} [Result.ErrorCode] Error code.
     *
     * @apiSuccessExample {json} Success response example:
     * {
     *	Module: 'Dav',
     *	Method: 'GetSettings',
     *	Result: [{ExternalHostNameOfDAVServer: 'host_value'}]
     * }
     *
     * @apiSuccessExample {json} Error response example:
     * {
     *	Module: 'Dav',
     *	Method: 'GetSettings',
     *	Result: false,
     *	ErrorCode: 102
     * }
     */
    /**
     * Obtains list of module settings for authenticated user.
     *
     * @return array
     */
    public function GetSettings()
    {
        \Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::Anonymous);

        return array(
            'ExternalHostNameOfDAVServer' => $this->GetServerUrl()
        );
    }

    /**
     * @api {post} ?/Api/ UpdateSettings
     * @apiName UpdateSettings
     * @apiGroup Dav
     * @apiDescription Updates module's settings - saves them to config.json file.
     *
     * @apiHeader {string} Authorization "Bearer " + Authentication token which was received as the result of Core.Login method.
     * @apiHeaderExample {json} Header-Example:
     *	{
     *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
     *	}
     *
     * @apiParam {string=Dav} Module Module name.
     * @apiParam {string=UpdateSettings} Method Method name.
     * @apiParam {string} Parameters JSON.stringified object <br>
     * {<br>
     * &emsp; **ExternalHostNameOfDAVServer** *string* External host name of DAV server.<br>
     * }
     *
     * @apiParamExample {json} Request-Example:
     * {
     *	Module: 'Dav',
     *	Method: 'UpdateSettings',
     *	Parameters: '{ ExternalHostNameOfDAVServer: "host_value" }'
     * }
     *
     * @apiSuccess {object[]} Result Array of response objects.
     * @apiSuccess {string} Result.Module Module name.
     * @apiSuccess {string} Result.Method Method name.
     * @apiSuccess {bool} Result.Result Indicates if settings were updated successfully.
     * @apiSuccess {int} [Result.ErrorCode] Error code.
     *
     * @apiSuccessExample {json} Success response example:
     * {
     *	Module: 'Dav',
     *	Method: 'UpdateSettings',
     *	Result: true
     * }
     *
     * @apiSuccessExample {json} Error response example:
     * {
     *	Module: 'Dav',
     *	Method: 'UpdateSettings',
     *	Result: false,
     *	ErrorCode: 102
     * }
     */
    /**
     * Updates module's settings - saves them to config.json file.
     *
     * @param string $ExternalHostNameOfDAVServer External host name of DAV server.
     * @return bool
     */
    public function UpdateSettings($ExternalHostNameOfDAVServer)
    {
        \Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::TenantAdmin);

        if (!empty($ExternalHostNameOfDAVServer)) {
            $this->setConfig('ExternalHostNameOfDAVServer', $ExternalHostNameOfDAVServer);
            $this->saveModuleConfig();
            return true;
        }

        return false;
    }

    /**
     * @api {post} ?/Api/ GetServerUrl
     * @apiName GetServerUrl
     * @apiGroup Dav
     * @apiDescription Returns DAV server URL.
     *
     * @apiParam {string=Dav} Module Module name.
     * @apiParam {string=GetServerUrl} Method Method name.
     *
     * @apiParamExample {json} Request-Example:
     * {
     *	Module: 'Dav',
     *	Method: 'GetServerUrl'
     * }
     *
     * @apiSuccess {object[]} Result Array of response objects.
     * @apiSuccess {string} Result.Module Module name.
     * @apiSuccess {string} Result.Method Method name.
     * @apiSuccess {mixed} Result.Result DAV server URL in case of success, otherwise **false**.
     * @apiSuccess {int} [Result.ErrorCode] Error code.
     *
     * @apiSuccessExample {json} Success response example:
     * {
     *	Module: 'Dav',
     *	Method: 'GetServerUrl',
     *	Result: 'url_value'
     * }
     *
     * @apiSuccessExample {json} Error response example:
     * {
     *	Module: 'Dav',
     *	Method: 'GetServerUrl',
     *	Result: false,
     *	ErrorCode: 102
     * }
     */
    /**
     * Returns DAV server URL.
     *
     * @return string
     */
    public function GetServerUrl()
    {
        \Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::Anonymous);

        return $this->getManager()->getServerUrl();
    }

    /**
     * @api {post} ?/Api/ GetServerHost
     * @apiName GetServerHost
     * @apiGroup Dav
     * @apiDescription Returns DAV server host.
     *
     * @apiParam {string=Dav} Module Module name.
     * @apiParam {string=GetServerHost} Method Method name.
     *
     * @apiParamExample {json} Request-Example:
     * {
     *	Module: 'Dav',
     *	Method: 'GetServerHost'
     * }
     *
     * @apiSuccess {object[]} Result Array of response objects.
     * @apiSuccess {string} Result.Module Module name.
     * @apiSuccess {string} Result.Method Method name.
     * @apiSuccess {mixed} Result.Result DAV server host in case of success, otherwise **false**.
     * @apiSuccess {int} [Result.ErrorCode] Error code.
     *
     * @apiSuccessExample {json} Success response example:
     * {
     *	Module: 'Dav',
     *	Method: 'GetServerHost',
     *	Result: 'host_value'
     * }
     *
     * @apiSuccessExample {json} Error response example:
     * {
     *	Module: 'Dav',
     *	Method: 'GetServerHost',
     *	Result: false,
     *	ErrorCode: 102
     * }
     */
    /**
     * Returns DAV server host.
     *
     * @return string
     */
    public function GetServerHost()
    {
        \Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::Anonymous);

        return $this->getManager()->getServerHost();
    }

    /**
     * @api {post} ?/Api/ GetServerPort
     * @apiName GetServerPort
     * @apiGroup Dav
     * @apiDescription Returns DAV server port.
     *
     * @apiParam {string=Dav} Module Module name.
     * @apiParam {string=GetServerPort} Method Method name.
     *
     * @apiParamExample {json} Request-Example:
     * {
     *	Module: 'Dav',
     *	Method: 'GetServerPort'
     * }
     *
     * @apiSuccess {object[]} Result Array of response objects.
     * @apiSuccess {string} Result.Module Module name.
     * @apiSuccess {string} Result.Method Method name.
     * @apiSuccess {mixed} Result.Result DAV server post in case of success, otherwise **false**.
     * @apiSuccess {int} [Result.ErrorCode] Error code.
     *
     * @apiSuccessExample {json} Success response example:
     * {
     *	Module: 'Dav',
     *	Method: 'GetServerPort',
     *	Result: 'port_value'
     * }
     *
     * @apiSuccessExample {json} Error response example:
     * {
     *	Module: 'Dav',
     *	Method: 'GetServerPort',
     *	Result: false,
     *	ErrorCode: 102
     * }
     */
    /**
     * Returns DAV server port.
     *
     * @return int
     */
    public function GetServerPort()
    {
        \Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::Anonymous);

        return $this->getManager()->getServerPort();
    }

    /**
     * Returns DAV principal URL.
     *
     * @return string
     */
    public function GetPrincipalUrl()
    {
        \Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
        $mResult = null;

        $oUser = \Aurora\System\Api::getAuthenticatedUser();
        if ($oUser) {
            $mResult = $this->getManager()->getPrincipalUrl($oUser->PublicId);
        }
        return $mResult;
    }

    /**
     * Returns **true** if connection to DAV should use SSL.
     *
     * @return bool
     */
    public function IsSsl()
    {
        \Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);

        return $this->getManager()->isSsl();
    }

    /**
     * Returns DAV login.
     *
     * @return string
     */
    public function GetLogin()
    {
        \Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
        $mResult = null;
        $oEntity = \Aurora\Modules\Core\Models\User::find(\Aurora\System\Api::getAuthenticatedUserId());
        if ($oEntity) {
            $mResult = $oEntity->PublicId;
        }

        return $mResult;
    }

    /**
     * Returns **true** if mobile sync enabled.
     *
     * @return bool
     */
    public function IsMobileSyncEnabled()
    {
        \Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);

        return $this->getManager()->isMobileSyncEnabled();
    }

    /**
     * Sets mobile sync enabled/disabled.
     *
     * @param bool $MobileSyncEnable Indicates if mobile sync should be enabled.
     * @return bool
     */
    public function SetMobileSyncEnable($MobileSyncEnable)
    {
        \Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);

        $oMobileSyncModule = \Aurora\System\Api::GetModule('MobileSync');
        $oMobileSyncModule->setConfig('Disabled', !$MobileSyncEnable);
        return $oMobileSyncModule->saveModuleConfig();
    }

    /**
     * Tests connection and returns **true** if connection was successful.
     *
     * @return bool
     */
    public function TestConnection()
    {
        \Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);

        return $this->getManager()->testConnection(
            \Aurora\System\Api::getAuthenticatedUserId()
        );
    }

    /**
     * Deletes principal.
     *
     * @return bool
     */
    public function DeletePrincipal()
    {
        \Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);

        return $this->getManager()->deletePrincipal(
            \Aurora\System\Api::getAuthenticatedUserId()
        );
    }

    /**
     * Returns public user.
     *
     * @return string
     */
    public function GetPublicUser()
    {
        \Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);

        return \Afterlogic\DAV\Constants::DAV_PUBLIC_PRINCIPAL;
    }

    /**
     *
     */
    public function Login($Login, $Password)
    {
        \Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::Anonymous);

        $oTenant = \Aurora\System\Api::getTenantByWebDomain();

        if (!$this->oModuleSettings->UseFullEmailForLogin) {
            $Login = $Login . '@' . $this->oModuleSettings->DomainForLoginWithoutEmail;
        }

        $mResult = \Aurora\Modules\Core\Module::Decorator()->Login($Login, $Password, '', false);

        if (is_array($mResult) && isset($mResult['AuthToken'])) {
            $sAuthToken = $mResult['AuthToken'];

            //this will store user data in static variable of Api class for later usage
            $oUser = \Aurora\System\Api::getAuthenticatedUser($sAuthToken);

            return array(
                'AuthToken' => $sAuthToken
            );
        }

        \Aurora\System\Api::LogEvent('login-failed: ' . $Login, self::GetName());
        if (!is_writable(\Aurora\System\Api::DataPath())) {
            throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::SystemNotConfigured);
        }
        throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::AuthError);
    }
    /***** public functions might be called with web API *****/
}
