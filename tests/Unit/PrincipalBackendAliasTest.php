<?php

namespace Aurora\Modules\Dav\Tests\Unit;

use Afterlogic\DAV\Principal\Backend\PDO;
use PHPUnit\Framework\TestCase;

/**
 * Principal backend without the database and event subscriptions: the users and the aliases
 * are taken from arrays.
 */
class TestablePrincipalBackend extends PDO
{
    /** @var string[] PublicIds of existing users */
    public $users = [];

    /** @var array Alias => PublicId of the owner */
    public $aliasOwners = [];

    protected function getPrincipalAliases($sUsername)
    {
        $aAliases = [];
        foreach ($this->aliasOwners as $sAlias => $sOwner) {
            if ($sOwner === $sUsername) {
                $aAliases[] = $sAlias;
            }
        }

        return $aAliases;
    }

    protected function userExistsByPublicId($sPublicId)
    {
        return in_array($sPublicId, $this->users, true);
    }

    protected function findPrincipalOwnerByAlias($sAlias)
    {
        return $this->aliasOwners[$sAlias] ?? null;
    }
}

/**
 * Tests resolving of alias e-mail addresses to the principal of the user who owns them
 */
class PrincipalBackendAliasTest extends TestCase
{
    private function backend(): TestablePrincipalBackend
    {
        $backend = new TestablePrincipalBackend();
        $backend->users = ['owner@example.com', 'other@example.com'];
        $backend->aliasOwners = ['alias@example.com' => 'owner@example.com'];

        return $backend;
    }

    public function testPrincipalPathContainsPrimaryAddressAndAliases(): void
    {
        $principal = $this->backend()->getPrincipalByPath('principals/owner@example.com');

        $this->assertSame('principals/owner@example.com', $principal['uri']);
        $this->assertSame('owner@example.com', $principal['{http://sabredav.org/ns}email-address']);
        $this->assertSame(
            ['mailto:owner@example.com', 'mailto:alias@example.com'],
            $principal['{DAV:}alternate-URI-set']
        );
    }

    public function testPrincipalWithoutAliasesHasOnlyPrimaryAddress(): void
    {
        $principal = $this->backend()->getPrincipalByPath('principals/other@example.com');

        $this->assertSame(['mailto:other@example.com'], $principal['{DAV:}alternate-URI-set']);
    }

    public function testFindByUriResolvesPrimaryAddress(): void
    {
        $this->assertSame(
            'principals/owner@example.com',
            $this->backend()->findByUri('mailto:owner@example.com', 'principals')
        );
    }

    public function testFindByUriResolvesAliasToOwnerPrincipal(): void
    {
        $this->assertSame(
            'principals/owner@example.com',
            $this->backend()->findByUri('mailto:alias@example.com', 'principals')
        );
    }

    public function testFindByUriReturnsNullForUnknownAddress(): void
    {
        $this->assertNull($this->backend()->findByUri('mailto:unknown@example.com', 'principals'));
    }

    public function testFindByUriReturnsNullForUnsupportedScheme(): void
    {
        $this->assertNull($this->backend()->findByUri('tel:+123456789', 'principals'));
    }

    public function testFindByUriReturnsNullForEmptyValue(): void
    {
        $this->assertNull($this->backend()->findByUri('mailto:', 'principals'));
    }
}
