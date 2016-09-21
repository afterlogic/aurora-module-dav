<?php
/*
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
	 * Broadcasts Login event to attempt to authenticate user. Method is called from mobile devices when dav-URL for syncronization is opened.
	 * 
	 * @param string $Login Account login.
	 * @param string $Password Account password.
	 * 
	 * @return boolean
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
		
		return $mResult;
	}
	
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
		
		return $this->oApiDavManager->getLogin(
			\CApi::getAuthenticatedUserId()
		);
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
