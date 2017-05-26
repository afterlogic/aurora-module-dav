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

class Manager extends \Aurora\System\Managers\AbstractManagerWithStorage
{
	/**
	 * @var array
	 */
	protected $aDavClients;

	/**
	 * 
	 * @param type $sForcedStorage
	 */
	public function __construct($sForcedStorage = '', \Aurora\System\Module\AbstractModule $oModule = null)
	{
		parent::__construct($oModule, new Storages\Db\Storage($this));
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
				$this->getServerUrl(), $iUserId, $iUserId);
		}

		if (isset($this->aDavClients[$iUserId]))
		{
			$mResult =& $this->aDavClients[$iUserId];
		}

		return $mResult;
	}

	/**
	 * @return string
	 */
	public function getServerUrl()
	{
		$sServerUrl = $this->oModule->getConfig('ExternalHostNameOfDAVServer', '');		
		if (empty($sServerUrl))
		{
			$sServerUrl = $this->GetModule()->oHttp->GetFullUrl().'dav.php';
		}
		
		return $sServerUrl;
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
	 * @return string
	 */
	public function getServerHost()
	{
		$mResult = '';
		$sServerUrl = $this->getServerUrl();
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
	 * @return bool
	 */
	public function isSsl()
	{
		$bResult = false;
		$sServerUrl = $this->getServerUrl();
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
	 * @return int
	 */
	public function getServerPort()
	{
		$iResult = 80;
		if ($this->isSsl())
		{
			$iResult = 443;
		}
			
		$sServerUrl = $this->getServerUrl();
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
			$sServerUrl = $this->getServerUrl();
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
		$oMobileSyncModule = \Aurora\System\Api::GetModule('MobileSync');
		return !$oMobileSyncModule->getConfig('Disabled');
	}

	/**
	 * 
	 * @param bool $bMobileSyncEnable
	 * 
	 * @return bool
	 */
	public function setMobileSyncEnable($bMobileSyncEnable)
	{
		$oMobileSyncModule = \Aurora\System\Api::GetModule('MobileSync');
		$oMobileSyncModule->setConfig('Disabled', !$bMobileSyncEnable);
		return $oMobileSyncModule->saveModuleConfig();
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
		$bResult = false;
		
		try
		{
			$sFilePath = dirname(__FILE__) . '/storages/db/sql/create.sql';
			$bResult = $this->oStorage->executeSqlFile($sFilePath);
		}
		catch (\Aurora\System\Exceptions\BaseException $oException)
		{
			$this->setLastException($oException);
		}

		return $bResult;
	}	
}
