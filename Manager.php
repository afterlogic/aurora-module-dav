<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\Dav;

use Aurora\Modules\Core\Module as CoreModule;
use Aurora\System\Api;
use Aurora\System\Application;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2023, Afterlogic Corp.
 *
 * @property Module $oModule
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
     * @param \Aurora\System\Classes\Account $oAccount
     * @return Client|false
     */
    public function &GetDAVClient($oAccount)
    {
        $mResult = false;

        if ($oAccount instanceof \Aurora\System\Classes\Account) {
            $login = $oAccount->getLogin();
            if (!isset($this->aDavClients[$login])) {
                $this->aDavClients[$login] = new Client(
                    $this->getServerUrl(),
                    $login,
                    $oAccount->getPassword()
                );
            }

            if (isset($this->aDavClients[$login])) {
                $mResult = &$this->aDavClients[$login];
            }
        }

        return $mResult;
    }

    /**
     * @return string
     */
    public function getServerUrl()
    {
        $sServerUrl = $this->oModule->oModuleSettings->ExternalHostNameOfDAVServer;
        if (empty($sServerUrl)) {
            $sServerUrl = Application::getBaseUrl() . 'dav.php/';
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
        if (!empty($sServerUrl)) {
            $aUrlParts = parse_url($sServerUrl);
            if (!empty($aUrlParts['host'])) {
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
        if (!empty($sServerUrl)) {
            $aUrlParts = parse_url($sServerUrl);
            if (!empty($aUrlParts['port']) && $aUrlParts['port'] === 443) {
                $bResult = true;
            }
            if (!empty($aUrlParts['scheme']) && $aUrlParts['scheme'] === 'https') {
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
        if ($this->isSsl()) {
            $iResult = 443;
        }

        $sServerUrl = $this->getServerUrl();
        if (!empty($sServerUrl)) {
            $aUrlParts = parse_url($sServerUrl);
            if (!empty($aUrlParts['port'])) {
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
        try {
            $sServerUrl = $this->getServerUrl();
            if (!empty($sServerUrl)) {
                $aUrlParts = parse_url($sServerUrl);
                $sPort = $sPath = '';
                if (!empty($aUrlParts['port']) && (int)$aUrlParts['port'] !== 80) {
                    $sPort = ':' . $aUrlParts['port'];
                }
                if (!empty($aUrlParts['path'])) {
                    $sPath = $aUrlParts['path'];
                }

                if (!empty($aUrlParts['scheme']) && !empty($aUrlParts['host'])) {
                    $sServerUrl = $aUrlParts['scheme'] . '://' . $aUrlParts['host'] . $sPort;

                    $mResult = $sServerUrl . \rtrim($sPath, '/') . '/' . \Afterlogic\DAV\Constants::PRINCIPALS_PREFIX . $iUserId;
                }
            }
        } catch (\Exception $oException) {
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
        $bResult = false;

        if (class_exists('\Aurora\Modules\MobileSync\Module')) {
            $oMobileSyncModule = \Aurora\Modules\MobileSync\Module::getInstance();
            $bResult = !$oMobileSyncModule->oModuleSettings->Disabled;
        }

        return $bResult;
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
     * @param int $UserId
     *
     * @return bool
     */
    public function testConnection($UserId)
    {
        $bResult = false;

        $Login = Api::getUserPublicIdById($UserId);
        if (!empty($Login)) {
            $oAccount = CoreModule::Decorator()->GetAccountUsedToAuthorize($Login);
            if ($oAccount) {
                $oDav = &$this->GetDAVClient($oAccount);
                if ($oDav && $oDav->Connect()) {
                    $bResult = true;
                }
            }
        }

        return $bResult;
    }

    /**
     * @param int $UserId
     */
    public function deletePrincipal($UserId) {}

    /**
     * @param string $sData
     * @return \Sabre\VObject\Document
     */
    public function getVCardObject($sData)
    {
        return \Sabre\VObject\Reader::read($sData, \Sabre\VObject\Reader::OPTION_IGNORE_INVALID_LINES);
    }
}
