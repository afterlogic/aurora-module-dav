<?php
/**
 * @copyright Copyright (c) 2017, Afterlogic Corp.
 * @license AGPL-3.0 or AfterLogic Software License
 *
 * This code is licensed under AGPLv3 license or AfterLogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\Dav;

/**
 * @package Modules
 */
class Module extends \Aurora\System\Module\AbstractModule
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
		
		$this->oApiDavManager = new Manager($this);
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
		if ($mResult)
		{
			$mResult = $this->oApiDavManager->createTablesFromFile();
		}
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
			throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
		});
		
		@set_time_limit(3000);
		
		if (false !== \strpos($this->oHttp->GetUrl(), '?dav'))
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
	 * @return \Aurora\Modules\Dav\Client|false
	 */
	public function GetDavClient()
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		
		return $this->oApiDavManager->GetDAVClient(\Aurora\System\Api::getAuthenticatedUserId());
	}
	
	/**
	 * Returns VCARD object.
	 * 
	 * @param string|resource $Data
	 * @return Document
	 */
	public function GetVCardObject($Data)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		
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
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::Anonymous);
		
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
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::Anonymous);
		
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
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::Anonymous);
		
		return $this->oApiDavManager->getServerPort();
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
		if($oUser)
		{
			$mResult = $this->oApiDavManager->getPrincipalUrl($oUser->PublicId);			
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
		
		return $this->oApiDavManager->isSsl();
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
		
		$oEntity = (new \Aurora\System\Managers\Eav())->getEntity(
			(int) \Aurora\System\Api::getAuthenticatedUserId(), '\Aurora\Modules\Core\Classes\User'
		);
		if (!empty($oEntity))
		{
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
		
		return $this->oApiDavManager->testConnection(
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
		
		return $this->oApiDavManager->deletePrincipal(
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
	/***** public functions might be called with web API *****/
}
