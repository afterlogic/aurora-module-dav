<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @package Dav
 */
class CApiDavManager extends AApiManagerWithStorage
{
	/**
	 * @var array
	 */
	protected $aDavClients;

	/**
	 * 
	 * @param CApiGlobalManager $oManager
	 * @param type $sForcedStorage
	 */
	public function __construct(CApiGlobalManager &$oManager, $sForcedStorage = '', AApiModule $oModule = null)
	{
		parent::__construct('', $oManager, $sForcedStorage, $oModule);

		$this->aDavClients = array();
	}

	/**
	 * @param int $iUserId
	 * @return CDAVClient|false
	 */
	public function &GetDAVClient($iUserId)
	{
		$mResult = false;
		if (!isset($this->aDavClients[$iUserId]))
		{
			$this->aDavClients[$iUserId] = new CDAVClient(
				$this->getServerUrl($iUserId), $iUserId, $iUserId);
		}

		if (isset($this->aDavClients[$iUserId]))
		{
			$mResult =& $this->aDavClients[$iUserId];
		}

		return $mResult;
	}

	/**
	 * @param CAccount $iUserId Default null
	 * 
	 * @return string
	 */
	public function getServerUrl($iUserId = null)
	{
		// TODO: 
		return $this->oModule->getConfig('ExternalHostNameOfDAVServer', '/');		
	}

	/**
	 * @return string
	 */
	public function getCalendarStorageType()
	{
		return $this->oManager->GetStorageByType('calendar');
	}

	/**
	 * @return string
	 */
	public function getContactsStorageType()
	{
		return $this->oManager->GetStorageByType('contactsmain');
	}

	/**
	 * @param CAccount $oAccount Default null
	 * 
	 * @return string
	 */
	public function getServerHost($oAccount = null)
	{
		$mResult = '';
		$sServerUrl = $this->getServerUrl($oAccount);
		if (!empty($sServerUrl))
		{
			$aUrlParts = parse_url($sServerUrl);
			if (!empty($aUrlParts['host']))
			{
				$mResult = $aUrlParts['host'];
			}
		}
		return $mResult;
	}

	/**
	 * @param CAccount $oAccount Default null
	 * 
	 * @return bool
	 */
	public function isUseSsl($oAccount = null)
	{
		$bResult = false;
		$sServerUrl = $this->getServerUrl($oAccount);
		if (!empty($sServerUrl))
		{
			$aUrlParts = parse_url($sServerUrl);
			if (!empty($aUrlParts['port']) && $aUrlParts['port'] === 443)
			{
				$bResult = true;
			}
			if (!empty($aUrlParts['scheme']) && $aUrlParts['scheme'] === 'https')
			{
				$bResult = true;
			}
		}
		return $bResult;
	}

	/**
	 * @param CAccount $oAccount Default null
	 * 
	 * @return int
	 */
	public function getServerPort($oAccount = null)
	{
		$iResult = 80;
		if ($this->isUseSsl($oAccount))
		{
			$iResult = 443;
		}
			
		$sServerUrl = $this->getServerUrl($oAccount);
		if (!empty($sServerUrl))
		{
			$aUrlParts = parse_url($sServerUrl);
			if (!empty($aUrlParts['port']))
			{
				$iResult = (int) $aUrlParts['port'];
			}
		}
		return $iResult;
	}

	/**
	 * @param int $iUserId
	 * 
	 * @return string
	 */
	public function getPrincipalUrl($iUserId)
	{
		$mResult = false;
		try
		{
			$sServerUrl = $this->getServerUrl($iUserId);
			if (!empty($sServerUrl))
			{
				$aUrlParts = parse_url($sServerUrl);
				$sPort = $sPath = '';
				if (!empty($aUrlParts['port']) && (int)$aUrlParts['port'] !== 80)
				{
					$sPort = ':'.$aUrlParts['port'];
				}
				if (!empty($aUrlParts['path']))
				{
					$sPath = $aUrlParts['path'];
				}

				if (!empty($aUrlParts['scheme']) && !empty($aUrlParts['host']))
				{
					$sServerUrl = $aUrlParts['scheme'].'://'.$aUrlParts['host'].$sPort;

					if ($this->getCalendarStorageType() === 'caldav' || $this->getContactsStorageType() === 'carddav')
					{
						$oDav =& $this->GetDAVClient($iUserId);
						if ($oDav && $oDav->Connect())
						{
							$mResult = $sServerUrl.$oDav->GetCurrentPrincipal();
						}
					}
					else
					{
						$mResult = $sServerUrl . $sPath .'/principals/' . $iUserId;
					}
				}
			}
		}
		catch (Exception $oException)
		{
			$mResult = false;
			$this->setLastException($oException);
		}
		return $mResult;
	}

	/**
	 * @param int $iUserId
	 * 
	 * @return string
	 */
	public function getLogin($iUserId)
	{
		return $iUserId;
	}

	/**
	 * @return bool
	 */
	public function isMobileSyncEnabled()
	{
		$oSettings =& CApi::GetSettings();
		return (bool) $oSettings->GetConf('EnableMobileSync');
	}

	/**
	 * 
	 * @param bool $bMobileSyncEnable
	 * 
	 * @return bool
	 */
	public function setMobileSyncEnable($bMobileSyncEnable)
	{
		$oSettings =& CApi::GetSettings();
		$oSettings->SetConf('Common/EnableMobileSync', $bMobileSyncEnable);
		return (bool) $oSettings->Save();
	}

	/**
	 * @param CAccount $oAccount
	 * 
	 * @return bool
	 */
	public function testConnection($oAccount)
	{
		$bResult = false;
		$oDav =& $this->GetDAVClient($oAccount);
		if ($oDav && $oDav->Connect())
		{
			$bResult = true;
		}
		return $bResult;
	}

	/**
	 * @param CAccount $oAccount
	 */
	public function deletePrincipal($oAccount)
	{
		$oPrincipalBackend = \Afterlogic\DAV\Backend::Principal();
		$oPrincipalBackend->deletePrincipal(\Afterlogic\DAV\Constants::PRINCIPALS_PREFIX . '/' . $oAccount->Email);
	}

	/**
	 * @param string $sData
	 * @return mixed
	 */
	public function getVCardObject($sData)
	{
		return \Sabre\VObject\Reader::read($sData, \Sabre\VObject\Reader::OPTION_IGNORE_INVALID_LINES);
	}
	
	/**
	 * Creates tables required for module work by executing create.sql file.
	 * 
	 * @return boolean
	 */
	public function createTablesFromFile()
	{
		$bResult = true;
		
		try
		{
			$sFilePath = dirname(__FILE__) . '/storages/db/sql/create.sql';
			$bResult = $this->oStorage->executeSqlFile($sFilePath);
		}
		catch (CApiBaseException $oException)
		{
			$this->setLastException($oException);
		}

		return $bResult;
	}	
}
