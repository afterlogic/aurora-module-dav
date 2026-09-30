<?php

namespace Aurora\Modules\Dav\Tests\Unit;

use Afterlogic\DAV\Backend;
use Afterlogic\DAV\CalDAV\Schedule\Plugin;
use PHPUnit\Framework\TestCase;
use Sabre\CalDAV\Backend\AbstractBackend;
use Sabre\CalDAV\Backend\SchedulingSupport;
use Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\ITip\Message as ITipMessage;

/**
 * CalDAV backend that keeps calendars, events and scheduling inboxes in memory
 */
class InMemoryCalDAVBackend extends AbstractBackend implements SchedulingSupport
{
    /** @var array principal uri => calendars */
    public $calendars = [];

    /** @var array calendar id => [object uri => data] */
    public $objects = [];

    /** @var array principal uri => [object uri => data] */
    public $inboxes = [];

    private $nextId = 1;

    public function addCalendar(string $principalUri, string $uri, ?array $components = ['VEVENT']): array
    {
        $calendar = [
            'id' => $this->nextId++,
            'uri' => $uri,
            'principaluri' => $principalUri,
            '{DAV:}displayname' => $uri,
        ];
        if ($components !== null) {
            $calendar['{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set'] = new SupportedCalendarComponentSet($components);
        }
        $this->calendars[$principalUri][] = $calendar;
        $this->objects[$calendar['id']] = [];

        return $calendar;
    }

    public function getCalendarsForUser($principalUri)
    {
        return $this->calendars[$principalUri] ?? [];
    }

    public function createCalendar($principalUri, $calendarUri, array $properties)
    {
        return $this->addCalendar($principalUri, $calendarUri)['id'];
    }

    public function deleteCalendar($calendarId)
    {
    }

    public function getCalendarObjects($calendarId)
    {
        $result = [];
        foreach ($this->objects[$calendarId] as $uri => $data) {
            $result[] = $this->getCalendarObject($calendarId, $uri);
        }

        return $result;
    }

    public function getCalendarObject($calendarId, $objectUri)
    {
        if (!isset($this->objects[$calendarId][$objectUri])) {
            return null;
        }
        $data = $this->objects[$calendarId][$objectUri];

        return [
            'id' => $objectUri,
            'uri' => $objectUri,
            'calendardata' => $data,
            'etag' => '"' . md5($data) . '"',
            'size' => strlen($data),
            'lastmodified' => time(),
            'calendarid' => $calendarId,
        ];
    }

    public function createCalendarObject($calendarId, $objectUri, $calendarData)
    {
        $this->objects[$calendarId][$objectUri] = $calendarData;

        return '"' . md5($calendarData) . '"';
    }

    public function updateCalendarObject($calendarId, $objectUri, $calendarData)
    {
        return $this->createCalendarObject($calendarId, $objectUri, $calendarData);
    }

    public function deleteCalendarObject($calendarId, $objectUri)
    {
        unset($this->objects[$calendarId][$objectUri]);
    }

    public function getCalendarObjectByUID($principalUri, $uid)
    {
        foreach ($this->getCalendarsForUser($principalUri) as $calendar) {
            foreach ($this->objects[$calendar['id']] as $uri => $data) {
                if (false !== strpos($data, 'UID:' . $uid)) {
                    return $calendar['uri'] . '/' . $uri;
                }
            }
        }

        return null;
    }

    public function getSchedulingObject($principalUri, $objectUri)
    {
        return null;
    }

    public function getSchedulingObjects($principalUri)
    {
        return [];
    }

    public function deleteSchedulingObject($principalUri, $objectUri)
    {
    }

    public function createSchedulingObject($principalUri, $objectUri, $objectData)
    {
        $this->inboxes[$principalUri][$objectUri] = $objectData;
    }
}

/**
 * Tests Schedule\Plugin::scheduleLocalDelivery(): a scheduling message is delivered to the
 * calendar and to the schedule inbox of the recipient, not of the organizer, and the recipient
 * can be given by an alias address.
 */
class CalDAVScheduleLocalDeliveryTest extends TestCase
{
    private const ORGANIZER = 'principals/organizer@example.com';
    private const ATTENDEE = 'principals/attendee@example.com';

    /** @var InMemoryCalDAVBackend */
    private $backend;

    private $savedBackends;

    protected function setUp(): void
    {
        $this->savedBackends = Backend::$aBackends;
        $this->backend = new InMemoryCalDAVBackend();
        Backend::$aBackends['caldav'] = $this->backend;
    }

    protected function tearDown(): void
    {
        Backend::$aBackends = $this->savedBackends;
    }

    private function plugin(array $principals, array $privileges = null, bool $hasHome = true, bool $hasAcl = true): Plugin
    {
        $caldavNs = '{urn:ietf:params:xml:ns:caldav}';
        $privileges = $privileges ?? [$caldavNs . 'schedule-deliver-invite', $caldavNs . 'schedule-deliver-reply'];

        $acl = $this->createStub(\Sabre\DAVACL\Plugin::class);
        $acl->method('getPrincipalByUri')->willReturnCallback(function ($uri) use ($principals) {
            return $principals[$uri] ?? null;
        });
        $acl->method('getCurrentUserPrivilegeSet')->willReturn($privileges);

        $caldav = $this->createStub(\Sabre\CalDAV\Plugin::class);
        $caldav->method('getCalendarHomeForPrincipal')->willReturnCallback(function ($principal) use ($hasHome) {
            return $hasHome ? 'calendars/' . basename($principal) : null;
        });

        $server = $this->createStub(\Sabre\DAV\Server::class);
        $server->method('getPlugin')->willReturnCallback(function ($name) use ($acl, $caldav, $hasAcl) {
            if ('acl' === $name) {
                return $hasAcl ? $acl : null;
            }

            return 'caldav' === $name ? $caldav : null;
        });

        $plugin = new Plugin();
        $property = new \ReflectionProperty(\Sabre\CalDAV\Schedule\Plugin::class, 'server');
        $property->setAccessible(true);
        $property->setValue($plugin, $server);

        return $plugin;
    }

    private function requestTo(string $recipient): ITipMessage
    {
        $vCal = new VCalendar();
        $event = $vCal->add('VEVENT');
        $event->add('UID', 'meeting-1');
        $event->add('DTSTART', new \DateTime('+1 year', new \DateTimeZone('UTC')));
        $event->add('DTEND', new \DateTime('+1 year +1 hour', new \DateTimeZone('UTC')));
        $event->add('SUMMARY', 'Meeting');
        $event->add('ORGANIZER', 'mailto:organizer@example.com');
        $event->add('ATTENDEE', $recipient);

        $message = new ITipMessage();
        $message->method = 'REQUEST';
        $message->uid = 'meeting-1';
        $message->component = 'VEVENT';
        $message->sequence = 0;
        $message->sender = 'mailto:organizer@example.com';
        $message->recipient = $recipient;
        $message->message = $vCal;
        $message->significantChange = true;

        return $message;
    }

    public function testMessageIsDeliveredToRecipientNotToOrganizer(): void
    {
        $organizerCalendar = $this->backend->addCalendar(self::ORGANIZER, 'MyCalendar-organizer');
        $attendeeCalendar = $this->backend->addCalendar(self::ATTENDEE, 'MyCalendar-attendee');
        $plugin = $this->plugin(['mailto:attendee@example.com' => self::ATTENDEE]);

        $message = $this->requestTo('mailto:attendee@example.com');
        $plugin->scheduleLocalDelivery($message);

        $this->assertSame('1.2;Message delivered locally', $message->scheduleStatus);
        $this->assertCount(1, $this->backend->objects[$attendeeCalendar['id']], 'The event is in the attendee calendar');
        $this->assertCount(0, $this->backend->objects[$organizerCalendar['id']], 'The organizer calendar is untouched');
        $this->assertArrayHasKey(self::ATTENDEE, $this->backend->inboxes, 'The message is in the attendee inbox');
        $this->assertCount(1, $this->backend->inboxes[self::ATTENDEE]);
        $this->assertArrayNotHasKey(self::ORGANIZER, $this->backend->inboxes, 'The organizer inbox is untouched');
    }

    public function testMessageToAliasIsDeliveredToOwnerOfTheAlias(): void
    {
        $attendeeCalendar = $this->backend->addCalendar(self::ATTENDEE, 'MyCalendar-attendee');
        $plugin = $this->plugin(['mailto:alias@example.com' => self::ATTENDEE]);

        $message = $this->requestTo('mailto:alias@example.com');
        $plugin->scheduleLocalDelivery($message);

        $this->assertSame('1.2;Message delivered locally', $message->scheduleStatus);
        $this->assertCount(1, $this->backend->objects[$attendeeCalendar['id']]);
        $this->assertCount(1, $this->backend->inboxes[self::ATTENDEE]);
    }

    public function testDeliveredEventKeepsRecipientDefaultCalendarPreference(): void
    {
        $this->backend->addCalendar(self::ATTENDEE, 'work');
        $default = $this->backend->addCalendar(self::ATTENDEE, 'MyCalendar-1234');
        $plugin = $this->plugin(['mailto:attendee@example.com' => self::ATTENDEE]);

        $plugin->scheduleLocalDelivery($this->requestTo('mailto:attendee@example.com'));

        $this->assertCount(1, $this->backend->objects[$default['id']]);
    }

    public function testUnknownRecipientIsNotDelivered(): void
    {
        $plugin = $this->plugin([]);

        $message = $this->requestTo('mailto:nobody@example.com');
        $plugin->scheduleLocalDelivery($message);

        $this->assertStringStartsWith('3.7;', $message->scheduleStatus);
        $this->assertSame([], $this->backend->inboxes);
    }

    public function testRecipientWithoutCalendarHomeIsNotDelivered(): void
    {
        $plugin = $this->plugin(['mailto:attendee@example.com' => self::ATTENDEE], null, false);

        $message = $this->requestTo('mailto:attendee@example.com');
        $plugin->scheduleLocalDelivery($message);

        $this->assertStringStartsWith('5.2;', $message->scheduleStatus);
        $this->assertSame([], $this->backend->inboxes);
    }

    public function testDeliveryWithoutInvitePrivilegeIsRejected(): void
    {
        $this->backend->addCalendar(self::ATTENDEE, 'MyCalendar-attendee');
        $plugin = $this->plugin(['mailto:attendee@example.com' => self::ATTENDEE], []);

        $message = $this->requestTo('mailto:attendee@example.com');
        $plugin->scheduleLocalDelivery($message);

        $this->assertStringStartsWith('3.8;', $message->scheduleStatus);
        $this->assertSame([], $this->backend->inboxes);
    }

    public function testRecipientWithoutCalendarForEventsIsNotDelivered(): void
    {
        $this->backend->addCalendar(self::ATTENDEE, 'reminders', ['VTODO']);
        $plugin = $this->plugin(['mailto:attendee@example.com' => self::ATTENDEE]);

        $message = $this->requestTo('mailto:attendee@example.com');
        $plugin->scheduleLocalDelivery($message);

        $this->assertStringStartsWith('5.2;', $message->scheduleStatus);
    }

    public function testNothingIsDeliveredWithoutAclPlugin(): void
    {
        $this->backend->addCalendar(self::ATTENDEE, 'MyCalendar-attendee');
        $plugin = $this->plugin(['mailto:attendee@example.com' => self::ATTENDEE], null, true, false);

        $message = $this->requestTo('mailto:attendee@example.com');
        $plugin->scheduleLocalDelivery($message);

        $this->assertNull($message->scheduleStatus);
        $this->assertSame([], $this->backend->inboxes);
    }
}
