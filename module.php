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
	
	public function EntryDav()
	{
		set_error_handler(function ($errno, $errstr, $errfile, $errline ) {
			
			throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
		});

		@set_time_limit(3000);

		$sBaseUri = '/';
		$oHttp = \MailSo\Base\Http::NewInstance();
		if (false !== \strpos($oHttp->GetUrl(), 'index.php/dav/')) {
			
			$aPath = \trim($oHttp->GetPath(), '/\\ ');
			$sBaseUri = (0 < \strlen($aPath) ? '/'.$aPath : '').'/index.php/dav/';
		}
		
		\Afterlogic\DAV\Server::getInstance($sBaseUri)->exec();
		return '';
	}	
	
	public function GetDavClient()
	{
		return $this->oApiDavManager->GetDAVClient(\CApi::getAuthenticatedUserId());
	}
	
	public function GetServerUrl()
	{
		return $this->oApiDavManager->getServerUrl(
			\CApi::getAuthenticatedUserId()
		);
	}
	
	public function GetServerHost()
	{
		return $this->oApiDavManager->getServerHost(
			\CApi::getAuthenticatedUserId()
		);
	}
	
	public function GetServerPort()
	{
		return $this->oApiDavManager->getServerPort(
			\CApi::getAuthenticatedUserId()
		);
	}
	
	public function GetPrincipalUrl()
	{
		return $this->oApiDavManager->getPrincipalUrl(
			\CApi::getAuthenticatedUserId()
		);
	}


	public function IsUseSsl()
	{
		return $this->oApiDavManager->IsUseSsl(
			\CApi::getAuthenticatedUserId()
		);
	}
	
	public function GetLogin()
	{
		return $this->oApiDavManager->getLogin(
			\CApi::getAuthenticatedUserId()
		);
	}
	
	public function IsMobileSyncEnabled()
	{
		return $this->oApiDavManager->isMobileSyncEnabled();
	}	
	
	public function SetMobileSyncEnable()
	{
		$bMobileSyncEnable = $this->getParamValue('MobileSyncEnable', false); 
		$oSettings =& CApi::GetSettings();
		$oSettings->SetConf('Common/EnableMobileSync', $bMobileSyncEnable);
		return (bool) $oSettings->Save();
	}	
	
	public function TestConnection()
	{
		return $this->oApiDavManager->testConnection(
			\CApi::getAuthenticatedUserId()
		);
	}	
	
	public function DeletePrincipal()
	{
		return $this->oApiDavManager->deletePrincipal(
			\CApi::getAuthenticatedUserId()
		);
	}	
	
	public function GetVCardObject($Data)
	{
		return $this->oApiDavManager->getVCardObject($Data);
	}	
	
	public function GetPublicUser()
	{
		return \Afterlogic\DAV\Constants::DAV_PUBLIC_PRINCIPAL;
	}
	
	public function onAfterGetCalendars(&$aParameters)
	{
		if (isset($aParameters['@Result']) && $aParameters['@Result'] !== false) {
			
			$aParameters['@Result']['ServerUrl'] = $this->GetServerUrl();
		}
	}
	
	public function Login($Login, $Password)
	{
		$mResult = false;
		$this->broadcastEvent('Login', array(
			array(
				'Login' => $Login,
				'Password' => $Password,
				'SignMe' =>false
			),
			&$mResult)
		);		
		
		return ($mResult !== false && isset($mResult['id'])) ? $mResult['id'] : false;
	}
	
    public function onGetMobileSyncInfo(&$aData)
	{
		$sDavLogin = $this->GetLogin();
		$sDavServer = $this->GetServerUrl();
		
		$aData['EnableDav'] = true;
		$aData['Dav']['Login'] = $sDavLogin;
		$aData['Dav']['Server'] = $sDavServer;
		$aData['Dav']['PrincipalUrl'] = $this->GetPrincipalUrl();
	}
}
