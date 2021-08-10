<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\Dav;

use Aurora\System\Application;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2019, Afterlogic Corp.
 */
class Manager extends \Aurora\System\Managers\AbstractManager
{
	/**
	 * @var array
	 */
	protected $aDavClients;

	/**
	 *
	 * @param \Aurora\System\Module\AbstractModule $oModule
	 */
	public function __construct(\Aurora\System\Module\AbstractModule $oModule = null)
	{
		parent::__construct($oModule);
		$this->aDavClients = array();
	}

	/**
	 * @param int $iUserId
	 * @return \Aurora\Modules\Dav\Client|false
	 */
	public function &GetDAVClient($iUserId)
	{
		$mResult = false;
		if (!isset($this->aDavClients[$iUserId]))
		{
			$this->aDavClients[$iUserId] = new Client(
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
			$sServerUrl = Application::getBaseUrl() .'dav.php/';
		}

		return \rtrim($sServerUrl, '/') . '/';
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

					$mResult = $sServerUrl . \rtrim($sPath, '/') .'/' . \Afterlogic\DAV\Constants::PRINCIPALS_PREFIX . $iUserId;
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
	 * @param \Aurora\Modules\StandardAuth\Classes\Account $oAccount
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
	 * @param \Aurora\Modules\StandardAuth\Classes\Account $oAccount
	 */
	public function deletePrincipal($oAccount)
	{
		$oPrincipalBackend = \Afterlogic\DAV\Backend::Principal();
		$oPrincipalBackend->deletePrincipal(\Afterlogic\DAV\Constants::PRINCIPALS_PREFIX . $oAccount->Email);
	}

	/**
	 * @param string $sData
	 * @return mixed
	 */
	public function getVCardObject($sData)
	{
		return \Sabre\VObject\Reader::read($sData, \Sabre\VObject\Reader::OPTION_IGNORE_INVALID_LINES);
	}
}
