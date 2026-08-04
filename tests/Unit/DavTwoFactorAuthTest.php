<?php

use Aurora\Api;
use Aurora\Modules\Dav\Module as DavModule;
use Aurora\Modules\TwoFactorAuth\Module as TwoFactorAuthModule;
use Aurora\Modules\StandardAuth\Module as StandardAuthModule;
use Aurora\Modules\Core\Module as CoreModule;
use Aurora\Modules\Core\Models\User;
use Aurora\System\Enums\UserRole;

class DavTwoFactorAuthTest extends PHPUnit\Framework\TestCase
{
    /** Public login (email) of an existing user. Required, set via -d dav_test_login=... */
    protected string $testLogin;

    /** Password of an existing user. Required, set via -d dav_test_password=... */
    protected string $testPassword;

    protected ?DavModule $davModule = null;
    protected ?TwoFactorAuthModule $twoFactorModule = null;

    protected bool $configCaptured = false;
    protected $originalSkip2FA = null;
    protected $originalAllowUsedDevices = null;

    protected ?int $modifiedUserId = null;
    protected $originalTwoFactorSecret = null;

    protected function setUp(): void
    {
        if (!defined('AU_APP_ROOT_PATH')) {
            define('AU_APP_ROOT_PATH', rtrim(realpath(__DIR__ . '/../../'), '\/') . '/');
        }

        require_once AU_APP_ROOT_PATH . 'system/autoload.php';
        require_once AU_APP_ROOT_PATH . 'vendor/autoload.php';

        if (!defined('AU_API_INIT')) {
            Api::Init(true);
        }

        Api::SetUserSession([]);

        $login = get_cfg_var('dav_test_login');
        $password = get_cfg_var('dav_test_password');

        if ($login === false || $login === '') {
            throw new \RuntimeException(
                "Test user login is not set.\n" .
                "Run the test with -d dav_test_login=..., for example:\n" .
                "vendor/bin/phpunit -d dav_test_login='user@example.com' -d dav_test_password='...' tests/Unit/DavTwoFactorAuthTest.php"
            );
        }

        if ($password === false || $password === '') {
            throw new \RuntimeException(
                "Test user password is not set.\n" .
                "Run the test with -d dav_test_password=..., for example:\n" .
                "vendor/bin/phpunit -d dav_test_login='user@example.com' -d dav_test_password='...' tests/Unit/DavTwoFactorAuthTest.php"
            );
        }

        $this->testLogin = $login;
        $this->testPassword = $password;
    }

    protected function tearDown(): void
    {
        if ($this->davModule !== null && $this->configCaptured) {
            $this->davModule->setConfig('Skip2FA', $this->originalSkip2FA);
            $this->davModule->saveModuleConfig();
        }

        if ($this->twoFactorModule !== null && $this->configCaptured) {
            $this->twoFactorModule->setConfig('AllowUsedDevices', $this->originalAllowUsedDevices);
            $this->twoFactorModule->saveModuleConfig();
        }

        if ($this->modifiedUserId !== null) {
            $oUser = Api::getUserById($this->modifiedUserId);
            if ($oUser !== null) {
                $oUser->setExtendedProp('TwoFactorAuth::Secret', $this->originalTwoFactorSecret);
                $oUser->save();
            }
        }
    }

    protected function ensureModuleLoaded(string $moduleName)
    {
        $module = Api::GetModule($moduleName);
        if ($module === false) {
            Api::GetModuleManager()->setModuleConfigValue($moduleName, 'Disabled', false);
            $moduleManager = Api::GetModuleManager();
            $reflection = new \ReflectionMethod($moduleManager, 'loadModule');
            $reflection->setAccessible(true);
            $reflection->invoke($moduleManager, $moduleName);
            $module = Api::GetModule($moduleName);
        }

        return $module;
    }

    public function testDavLoginSkipsTwoFactorAuthWhenSkip2FAEnabled()
    {
        $davModule = $this->ensureModuleLoaded('Dav');
        $twoFactorModule = $this->ensureModuleLoaded('TwoFactorAuth');
        $standardAuthModule = $this->ensureModuleLoaded('StandardAuth');
        $coreModule = $this->ensureModuleLoaded('Core');

        $this->assertNotFalse($davModule, 'DAV module should be loaded');
        $this->assertNotFalse($twoFactorModule, 'TwoFactorAuth module should be loaded');
        $this->assertNotFalse($standardAuthModule, 'StandardAuth module should be loaded');
        $this->assertNotFalse($coreModule, 'Core module should be loaded');

        $davModule->loadModuleSettings();
        $twoFactorModule->loadModuleSettings();

        $this->davModule = $davModule;
        $this->twoFactorModule = $twoFactorModule;

        $this->originalSkip2FA = $davModule->getConfig('Skip2FA');
        $this->originalAllowUsedDevices = $twoFactorModule->getConfig('AllowUsedDevices');
        $this->configCaptured = true;

        $davModule->setConfig('Skip2FA', true);
        $twoFactorModule->setConfig('AllowUsedDevices', true);
        $this->assertTrue($davModule->saveModuleConfig(), 'DAV module config should be saved');
        $this->assertTrue($twoFactorModule->saveModuleConfig(), 'TwoFactorAuth module config should be saved');

        $publicId = $this->testLogin;
        $password = $this->testPassword;

        $coreModule->loadModuleSettings();

        $oUser = $coreModule->getUsersManager()->getUserByPublicId($publicId);
        $this->assertInstanceOf(User::class, $oUser, 'Existing test user should be found by public ID');
        $userId = $oUser->Id;

        $this->originalTwoFactorSecret = $oUser->getExtendedProp('TwoFactorAuth::Secret');
        $this->modifiedUserId = $userId;

        $oUser->setExtendedProp('TwoFactorAuth::Secret', 'dummy-secret');
        $this->assertTrue($oUser->save(), 'User 2FA secret should be saved');

        $davLoginResult = $davModule->Login($publicId, $password);
        $this->assertIsArray($davLoginResult);
        $this->assertArrayHasKey(\Aurora\System\Application::AUTH_TOKEN_KEY, $davLoginResult);
    }
}