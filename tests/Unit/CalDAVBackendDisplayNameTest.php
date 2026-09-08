<?php

use Afterlogic\DAV\CalDAV\Backend\PDO as CalDAVBackendPDO;

class TestableCalDAVBackend extends CalDAVBackendPDO
{
    public function __construct(\PDO $pdo)
    {
        // Bypass parent constructor which requires Aurora API
        $this->pdo = $pdo;
        $this->calendarTableName = 'calendars';
        $this->calendarChangesTableName = 'calendarchanges';
        $this->calendarObjectTableName = 'calendarobjects';
        $this->calendarInstancesTableName = 'calendarinstances';
        $this->schedulingObjectTableName = 'schedulingobjects';
        $this->calendarSubscriptionsTableName = 'calendarsubscriptions';

        // Set up propertyMap so parent::createCalendar and parent::updateCalendar work
        $this->propertyMap = [
            '{DAV:}displayname' => 'displayname',
            '{urn:ietf:params:xml:ns:caldav}calendar-description' => 'description',
            '{http://apple.com/ns/ical/}calendar-order' => 'calendarorder',
            '{http://apple.com/ns/ical/}calendar-color' => 'calendarcolor',
        ];
    }
}

class CalDAVBackendDisplayNameTest extends \PHPUnit\Framework\TestCase
{
    private $pdo;
    private $backend;

    protected function setUp(): void
    {
        $this->pdo = new \PDO('sqlite::memory:');
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec('
            CREATE TABLE calendars (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                synctoken INTEGER DEFAULT 1,
                components TEXT
            )
        ');

        $this->pdo->exec('
            CREATE TABLE calendarinstances (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                calendarid INTEGER,
                principaluri TEXT,
                uri TEXT,
                transparent INTEGER,
                displayname TEXT,
                description TEXT,
                timezone TEXT,
                calendarorder INTEGER,
                calendarcolor TEXT,
                synctoken INTEGER,
                components TEXT,
                access INTEGER DEFAULT 1,
                public INTEGER DEFAULT 0
            )
        ');

        $this->pdo->exec('
            CREATE TABLE calendarchanges (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uri TEXT,
                synctoken INTEGER,
                calendarid INTEGER,
                operation INTEGER
            )
        ');

        $this->backend = new TestableCalDAVBackend($this->pdo);
    }

    public function testCreateCalendarStripsTagsFromDisplayname()
    {
        $maliciousName = '<img src=x onerror=alert("xss")>My Calendar';
        $expected = 'My Calendar';

        $id = $this->backend->createCalendar(
            'principals/testuser',
            'cal-001',
            [
                '{DAV:}displayname' => $maliciousName,
                '{urn:ietf:params:xml:ns:caldav}calendar-description' => 'Test Description',
                '{http://apple.com/ns/ical/}calendar-order' => 1,
                '{http://apple.com/ns/ical/}calendar-color' => '#ff0000',
            ]
        );

        $stmt = $this->pdo->prepare(
            'SELECT displayname FROM calendarinstances WHERE uri = ?'
        );
        $stmt->execute(['cal-001']);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->assertNotFalse($row, 'Calendar instance should exist');
        $this->assertSame($expected, $row['displayname'],
            'displayname should have HTML tags stripped by strip_tags()');
    }

    public function testUpdateCalendarStripsTagsFromDisplayname()
    {
        $this->backend->createCalendar(
            'principals/testuser',
            'cal-002',
            [
                '{DAV:}displayname' => 'Original Name',
                '{urn:ietf:params:xml:ns:caldav}calendar-description' => '',
                '{http://apple.com/ns/ical/}calendar-order' => 1,
            ]
        );

        $stmt = $this->pdo->prepare('SELECT id FROM calendarinstances WHERE uri = ?');
        $stmt->execute(['cal-002']);
        $instance = $stmt->fetch(\PDO::FETCH_ASSOC);
        $instanceId = $instance['id'];

        $stmt = $this->pdo->prepare(
            'SELECT calendarid FROM calendarinstances WHERE id = ?'
        );
        $stmt->execute([$instanceId]);
        $instance = $stmt->fetch(\PDO::FETCH_ASSOC);
        $calendarId = $instance['calendarid'];

        $maliciousName = '<img src=x onerror=alert(1)>Updated Name';
        $expected = 'Updated Name';

        $propPatch = new \Sabre\DAV\PropPatch([
            '{DAV:}displayname' => $maliciousName,
        ]);

        $this->backend->updateCalendar([$calendarId, $instanceId], $propPatch);
        $propPatch->commit();

        $stmt = $this->pdo->prepare(
            'SELECT displayname FROM calendarinstances WHERE id = ?'
        );
        $stmt->execute([$instanceId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->assertSame($expected, $row['displayname'],
            'displayname should have HTML tags stripped during updateCalendar');
    }

    public function testCreateCalendarPreservesCleanDisplayname()
    {
        $safeName = 'My Clean Calendar';

        $this->backend->createCalendar(
            'principals/testuser',
            'cal-003',
            [
                '{DAV:}displayname' => $safeName,
                '{http://apple.com/ns/ical/}calendar-order' => 1,
            ]
        );

        $stmt = $this->pdo->prepare(
            'SELECT displayname FROM calendarinstances WHERE uri = ?'
        );
        $stmt->execute(['cal-003']);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->assertSame($safeName, $row['displayname'],
            'Clean displayname should be preserved unchanged');
    }
}
