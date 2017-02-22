<?php
/**
 * @copyright Copyright (c) 2016, Afterlogic Corp.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 * 
 * @package Modules
 */

namespace Aurora\Modules;

class DavModule extends \AApiModule
{
	public $oApiDavManager = null;
	
	/***** private functions *****/
	/**
	 * Initializes DAV Module.
	 * 
	 * @ignore
	 */
	public function init()
	{
		parent::init();
		
		$this->incClass('dav-client');
		
		$this->oApiDavManager = $this->GetManager();
		$this->AddEntry('dav', 'EntryDav');
		
		$this->subscribeEvent('Calendar::GetCalendars::after', array($this, 'onAfterGetCalendars'));
		$this->subscribeEvent('MobileSync::GetInfo', array($this, 'onGetMobileSyncInfo'));
		$this->subscribeEvent('Core::CreateTables::after', array($this, 'onAfterCreateTables'));
	}
	
	/**
	 * Writes in $aParameters DAV server URL.
	 * 
	 * @ignore
	 * @param array $aArgs
	 */
	public function onAfterGetCalendars(&$aArgs, &$mResult)
	{
		if (isset($mResult) && $mResult !== false)
		{
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
		$sDavLogin = $this->GetLogin();
		$sDavServer = $this->GetServerUrl();
		
		$mResult['EnableDav'] = true;
		$mResult['Dav']['Login'] = $sDavLogin;
		$mResult['Dav']['Server'] = $sDavServer;
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
		$mResult = $this->oApiDavManager->createTablesFromFile();
	}
	/***** private functions *****/
	
	/***** public functions *****/
	/**
	 * @ignore
	 * @return string
	 */
	public function EntryDav()
	{
		set_error_handler(function ($errno, $errstr, $errfile, $errline ) {
			throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
		});
		
		@set_time_limit(3000);
		
		if (false !== \strpos($this->oHttp->GetUrl(), '/?dav/'))
		{
			$aPath = \trim($this->oHttp->GetPath(), '/\\ ');
			$sBaseUri = (0 < \strlen($aPath) ? '/'.$aPath : '').'/?dav/';
		}
		\Afterlogic\DAV\Server::getInstance($sBaseUri)->exec();
		return '';
	}
	
	/**
	 * Returns DAV client.
	 * 
	 * @return CDAVClient|false
	 */
	public function GetDavClient()
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		return $this->oApiDavManager->GetDAVClient(\CApi::getAuthenticatedUserId());
	}
	
	/**
	 * Returns VCARD object.
	 * 
	 * @param string|resource $Data
	 * @return Document
	 */
	public function GetVCardObject($Data)
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		return $this->oApiDavManager->getVCardObject($Data);
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
	 * @apiParam {string=Dav} Module Module name.
	 * @apiParam {string=GetSettings} Method Method name.
	 * @apiParam {string} [AuthToken] Auth token.
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Dav',
	 *	Method: 'GetSettings',
	 *	AuthToken: 'token_value'
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
		\CApi::checkUserRoleIsAtLeast(\EUserRole::Anonymous);
		
		return array(
			'ExternalHostNameOfDAVServer' => $this->getConfig('ExternalHostNameOfDAVServer', ''),
		);
	}
	
	/**
	 * @api {post} ?/Api/ UpdateSettings
	 * @apiName UpdateSettings
	 * @apiGroup Dav
	 * @apiDescription Updates module's settings - saves them to config.json file.
	 * 
	 * @apiParam {string=Dav} Module Module name.
	 * @apiParam {string=UpdateSettings} Method Method name.
	 * @apiParam {string} AuthToken Auth token.
	 * @apiParam {string} Parameters JSON.stringified object <br>
	 * {<br>
	 * &emsp; **ExternalHostNameOfDAVServer** *string* External host name of DAV server.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Dav',
	 *	Method: 'UpdateSettings',
	 *	AuthToken: 'token_value',
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
		\CApi::checkUserRoleIsAtLeast(\EUserRole::TenantAdmin);
		
		if (!empty($ExternalHostNameOfDAVServer))
		{
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
		\CApi::checkUserRoleIsAtLeast(\EUserRole::Anonymous);
		
		return $this->oApiDavManager->getServerUrl();
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
		\CApi::checkUserRoleIsAtLeast(\EUserRole::Anonymous);
		
		return $this->oApiDavManager->getServerHost();
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
		\CApi::checkUserRoleIsAtLeast(\EUserRole::Anonymous);
		
		return $this->oApiDavManager->getServerPort();
	}
	
	/**
	 * Returns DAV principal URL.
	 * 
	 * @return string
	 */
	public function GetPrincipalUrl()
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		$oUser = \CApi::getAuthenticatedUser();
		if($oUser)
		{
			$sUUID = $oUser->UUID;
		}
		return $this->oApiDavManager->getPrincipalUrl($sUUID);
	}
	
	/**
	 * Returns **true** if connection to DAV should use SSL.
	 * 
	 * @return bool
	 */
	public function IsSsl()
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		return $this->oApiDavManager->isSsl();
	}
	
	/**
	 * Returns DAV login.
	 * 
	 * @return string
	 */
	public function GetLogin()
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		$oManagerApi = \CApi::GetSystemManager('eav', 'db');
		$oEntity = $oManagerApi->getEntity((int) \CApi::getAuthenticatedUserId());
		return $oEntity->UUID;
	}
	
	/**
	 * Returns **true** if mobile sync enabled.
	 * 
	 * @return bool
	 */
	public function IsMobileSyncEnabled()
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		return $this->oApiDavManager->isMobileSyncEnabled();
	}
	
	/**
	 * Sets mobile sync enabled/disabled.
	 * 
	 * @param bool $MobileSyncEnable Indicates if mobile sync should be enabled.
	 * @return bool
	 */
	public function SetMobileSyncEnable($MobileSyncEnable)
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		$oSettings =& \CApi::GetSettings();
		$oSettings->SetConf('Common/EnableMobileSync', $MobileSyncEnable);
		return (bool) $oSettings->Save();
	}
	
	/**
	 * Tests connection and returns **true** if connection was successful.
	 * 
	 * @return bool
	 */
	public function TestConnection()
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		return $this->oApiDavManager->testConnection(
			\CApi::getAuthenticatedUserId()
		);
	}
	
	/**
	 * Deletes principal.
	 * 
	 * @return bool
	 */
	public function DeletePrincipal()
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		return $this->oApiDavManager->deletePrincipal(
			\CApi::getAuthenticatedUserId()
		);
	}
	
	/**
	 * Returns public user.
	 * 
	 * @return string
	 */
	public function GetPublicUser()
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		return \Afterlogic\DAV\Constants::DAV_PUBLIC_PRINCIPAL;
	}
	/***** public functions might be called with web API *****/
}
