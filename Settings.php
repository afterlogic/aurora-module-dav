<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\Dav;

use Aurora\System\SettingsProperty;

/**
 * @property bool $Disabled
 * @property bool $AdminPrincipal
 * @property bool $CaldavNotification
 * @property bool $FilesSharing
 * @property bool $LogBody
 * @property bool $UseBrowserPlugin
 * @property bool $UseDigestAuth
 * @property string $ExternalHostNameOfDAVServer
 * @property string $ProductUrlForExternalClients
 * @property bool $UseFullEmailForLogin
 * @property string $DomainForLoginWithoutEmail
 */

class Settings extends \Aurora\System\Module\Settings
{
    protected function initDefaults()
    {
        $this->aContainer = [
            "Disabled" => new SettingsProperty(
                false,
                "bool",
                null,
                "Setting to true disables the module",
            ),
            "AdminPrincipal" => new SettingsProperty(
                false,
                "bool",
                null,
                "Email address specified here will automatically be added to the list of ACL's. They will effectively receive {DAV:}all privileges, as a protected privilege",
            ),
            "CaldavNotification" => new SettingsProperty(
                false,
                "bool",
                null,
                "Reserved for future use",
            ),
            "FilesSharing" => new SettingsProperty(
                false,
                "bool",
                null,
                "Not currently used",
            ),
            "LogBody" => new SettingsProperty(
                false,
                "bool",
                null,
                "Enables verbose logging of DAV server responses to sabredav- log file",
            ),
            "UseBrowserPlugin" => new SettingsProperty(
                false,
                "bool",
                null,
                "If true, allows for accessing DAV URL from web browser and navigating the account structure and data",
            ),
            "UseDigestAuth" => new SettingsProperty(
                false,
                "bool",
                null,
                "If true, enables the use of Digest auth instead of default Basic auth",
            ),
            "ExternalHostNameOfDAVServer" => new SettingsProperty(
                "",
                "string",
                null,
                "Denotes hostname for DAV server access",
            ),
            "ProductUrlForExternalClients" => new SettingsProperty(
                "",
                "string",
                null,
                "Product url for external clients",
            ),
            "UseFullEmailForLogin" => new SettingsProperty(
                true,
                "bool",
                null,
                "If true, full email address is used for DAV login; if fals, username part of email address is used",
            ),
            "DomainForLoginWithoutEmail" => new SettingsProperty(
                "",
                "string",
                null,
                "If UseFullEmailForLogin is false, this value denotes domain part of user's email address",
            ),
        ];
    }
}
