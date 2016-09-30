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

class DavModule extends AApiModule
{
	public $oApiDavManager = null;
	
	protected $aSettingsMap = array(
		'ExternalHostNameOfDAVServer' => array('', 'string')
	);
	
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
	 * @param array $aParameters
	 */
	public function onAfterGetCalendars(&$aParameters)
	{
		if (isset($aParameters['@Result']) && $aParameters['@Result'] !== false)
		{
			$aParameters['@Result']['ServerUrl'] = $this->GetServerUrl();
		}
	}
	
	/**
	 * Writes in $aData information about DAV server.
	 * 
	 * @ignore
	 * @param array $aData
	 */
    public function onGetMobileSyncInfo(&$aData)
	{
		$sDavLogin = $this->GetLogin();
		$sDavServer = $this->GetServerUrl();
		
		$aData['EnableDav'] = true;
		$aData['Dav']['Login'] = $sDavLogin;
		$aData['Dav']['Server'] = $sDavServer;
		$aData['Dav']['PrincipalUrl'] = $this->GetPrincipalUrl();
	}
	
	/**
	 * Creates tables required for module work. Called by event subscribe.
	 * 
	 * @ignore
	 * @param array $aParams Parameters
	 */
	public function onAfterCreateTables($aParams)
	{
		$aParams['@Result'] = $this->oApiDavManager->createTablesFromFile();
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
	 * @api {post} ?/Api/ GetAppData
	 * @apiName GetAppData
	 * @apiGroup Dav
	 * @apiDescription Obtaines list of module settings for authenticated user.
	 * 
	 * @apiParam {string=Dav} Module Module name.
	 * @apiParam {string=GetAppData} Method Method name.
	 * @apiParam {string} AuthToken Auth token.
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Dav',
	 *	Method: 'GetAppData',
	 *	AuthToken: 'token_value'
	 * }
	 * 
	 * @apiSuccess {string} Module Module name.
	 * @apiSuccess {string} Method Method name.
	 * @apiSuccess {mixed} Result Object in case of success, otherwise - false.
	 * @apiSuccess {string} Result.ExternalHostNameOfDAVServer External host name of DAV server.
	 * @apiSuccess {int} [ErrorCode] Error code.
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'Dav',
	 *	Method: 'GetAppData',
	 *	Result: [{ExternalHostNameOfDAVServer: 'host_value'}]
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'Dav',
	 *	Method: 'GetAppData',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
	/**
	 * Obtaines list of module settings for authenticated user.
	 * 
	 * @return array
	 */
	public function GetAppData()
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
	 * @apiParam {string} ExternalHostNameOfDAVServer External host name of DAV server.
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Dav',
	 *	Method: 'UpdateSettings',
	 *	AuthToken: 'token_value',
	 *  ExternalHostNameOfDAVServer: 'host_value'
	 * }
	 * 
	 * @apiSuccess {string} Module Module name.
	 * @apiSuccess {string} Method Method name.
	 * @apiSuccess {bool} Result Indicates if settings were updated successfully.
	 * @apiSuccess {int} [ErrorCode] Error code.
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
	 * @api {post} ?/Api/ Login
	 * @apiName Login
	 * @apiGroup Dav
	 * @apiDescription Broadcasts Login event to attempt to authenticate user. Method is called from mobile devices when dav-URL for syncronization is opened.
	 * 
	 * @apiParam {string=Dav} Module Module name.
	 * @apiParam {string=Login} Method Method name.
	 * @apiParam {string} AuthToken Auth token.
	 * @apiParam {string} Login Account login.
	 * @apiParam {string} Password Account password.
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Dav',
	 *	Method: 'Login',
	 *	AuthToken: 'token_value',
	 *  Login: 'login_value',
	 *  Password: 'password_value'
	 * }
	 * 
	 * @apiSuccess {string} Module Module name.
	 * @apiSuccess {string} Method Method name.
	 * @apiSuccess {bool} Result Indicates if logging in was successful.
	 * @apiSuccess {int} [ErrorCode] Error code.
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'Dav',
	 *	Method: 'Login',
	 *	Result: true
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'Dav',
	 *	Method: 'Login',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
	/**
	 * Broadcasts Login event to attempt to authenticate user. Method is called from mobile devices when dav-URL for syncronization is opened.
	 * 
	 * @param string $Login Account login.
	 * @param string $Password Account password.
	 * 
	 * @return bool
	 */
	public function Login($Login, $Password)
	{
		$mResult = false;
		$this->broadcastEvent('Login', array(
			array (
				'Login' => $Login,
				'Password' => $Password,
				'SignMe' => false
			),
			&$mResult
		));
		
		if (isset($mResult['id']))
		{
			$oManagerApi = \CApi::GetSystemManager('eav', 'db');
			$oEntity = $oManagerApi->getEntityById((int) $mResult['id']);
			$mResult = $oEntity->sUUID;
		}
		else 
		{
			$mResult = false;
		}
		
		return $mResult;
	}
	
	/**
	 * @api {post} ?/Api/ GetServerUrl
	 * @apiName GetServerUrl
	 * @apiGroup Dav
	 * @apiDescription Returns DAV server URL.
	 * 
	 * @apiParam {string=Dav} Module Module name.
	 * @apiParam {string=GetServerUrl} Method Method name.
	 * @apiParam {string} AuthToken Auth token.
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Dav',
	 *	Method: 'GetServerUrl',
	 *	AuthToken: 'token_value'
	 * }
	 * 
	 * @apiSuccess {string} Module Module name.
	 * @apiSuccess {string} Method Method name.
	 * @apiSuccess {mixed} Result DAV server URL in case of success, otherwise - false.
	 * @apiSuccess {int} [ErrorCode] Error code.
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
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		return $this->oApiDavManager->getServerUrl(
			\CApi::getAuthenticatedUserId()
		);
	}
	
	/**
	 * @api {post} ?/Api/ GetServerHost
	 * @apiName GetServerHost
	 * @apiGroup Dav
	 * @apiDescription Returns DAV server host.
	 * 
	 * @apiParam {string=Dav} Module Module name.
	 * @apiParam {string=GetServerHost} Method Method name.
	 * @apiParam {string} AuthToken Auth token.
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Dav',
	 *	Method: 'GetServerHost',
	 *	AuthToken: 'token_value'
	 * }
	 * 
	 * @apiSuccess {string} Module Module name.
	 * @apiSuccess {string} Method Method name.
	 * @apiSuccess {mixed} Result DAV server host in case of success, otherwise - false.
	 * @apiSuccess {int} [ErrorCode] Error code.
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
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		return $this->oApiDavManager->getServerHost(
			\CApi::getAuthenticatedUserId()
		);
	}
	
	/**
	 * @api {post} ?/Api/ GetServerPort
	 * @apiName GetServerPort
	 * @apiGroup Dav
	 * @apiDescription Returns DAV server port.
	 * 
	 * @apiParam {string=Dav} Module Module name.
	 * @apiParam {string=GetServerPort} Method Method name.
	 * @apiParam {string} AuthToken Auth token.
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'Dav',
	 *	Method: 'GetServerPort',
	 *	AuthToken: 'token_value'
	 * }
	 * 
	 * @apiSuccess {string} Module Module name.
	 * @apiSuccess {string} Method Method name.
	 * @apiSuccess {mixed} Result DAV server post in case of success, otherwise - false.
	 * @apiSuccess {int} [ErrorCode] Error code.
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
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		return $this->oApiDavManager->getServerPort(
			\CApi::getAuthenticatedUserId()
		);
	}
	
	/**
	 * Returns DAV principal URL.
	 * 
	 * @return string
	 */
	public function GetPrincipalUrl()
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		return $this->oApiDavManager->getPrincipalUrl(
			\CApi::getAuthenticatedUserId()
		);
	}
	
	/**
	 * Returns **true** if connection to DAV should use SSL.
	 * 
	 * @return bool
	 */
	public function IsUseSsl()
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		return $this->oApiDavManager->IsUseSsl(
			\CApi::getAuthenticatedUserId()
		);
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
		$oEntity = $oManagerApi->getEntityById((int) \CApi::getAuthenticatedUserId());
		return $oEntity->sUUID;
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
		
		$oSettings =& CApi::GetSettings();
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
