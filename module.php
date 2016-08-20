<?php

class DavModule extends AApiModule
{
	public $oApiDavManager = null;
	
	protected $aSettingsMap = array(
		'ExternalHostNameOfDAVServer' => array('', 'string')
	);
	
	public function init()
	{
		parent::init();

		$this->incClass('dav-client');
		
		$this->oApiDavManager = $this->GetManager();
		$this->AddEntry('dav', 'EntryDav');
		
		$this->subscribeEvent('Calendar::GetCalendars::after', array($this, 'onAfterGetCalendars'));
		$this->subscribeEvent('MobileSync::GetInfo', array($this, 'onGetMobileSyncInfo'));
	}
	
	/***** private functions *****/
	/**
	 * Writes in $aParameters DAV server URL.
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

		$sBaseUri = '/';
		$oHttp = \MailSo\Base\Http::NewInstance();
		if (false !== \strpos($oHttp->GetUrl(), 'index.php/dav/'))
		{
			$aPath = \trim($oHttp->GetPath(), '/\\ ');
			$sBaseUri = (0 < \strlen($aPath) ? '/'.$aPath : '').'/index.php/dav/';
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
		return $this->oApiDavManager->GetDAVClient(\CApi::getAuthenticatedUserId());
	}
	
	/**
	 * Returns VCARD object.
	 * @param string|resource $Data
	 * @return Document
	 */
	public function GetVCardObject($Data)
	{
		return $this->oApiDavManager->getVCardObject($Data);
	}	
	/***** public functions *****/
	
	/***** public functions might be called with web API *****/
	/**
	 * Returns DAV server URL.
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
	 * @return bool
	 */
	public function IsMobileSyncEnabled()
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		return $this->oApiDavManager->isMobileSyncEnabled();
	}
	
	/**
	 * Sets mobile sync enabled/disabled.
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
	 * @return string
	 */
	public function GetPublicUser()
	{
		\CApi::checkUserRoleIsAtLeast(\EUserRole::NormalUser);
		
		return \Afterlogic\DAV\Constants::DAV_PUBLIC_PRINCIPAL;
	}
	/***** public functions might be called with web API *****/
}
